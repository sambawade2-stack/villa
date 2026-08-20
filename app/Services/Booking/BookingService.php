<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\BlockReason;
use App\Enums\BookingStatus;
use App\Enums\CommissionStatus;
use App\Exceptions\BookingNotAllowedException;
use App\Exceptions\DatesUnavailableException;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Commission;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use App\Services\Pricing\PricingService;
use App\Support\BookingReference;
use App\Support\Money;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cycle de vie d'une réservation.
 *
 * Point unique d'écriture : rien d'autre dans l'application n'a le droit de
 * changer le statut d'une réservation ni de poser un blocage de calendrier.
 */
class BookingService
{
    /** Code SQLSTATE d'une violation de contrainte d'exclusion PostgreSQL. */
    private const EXCLUSION_VIOLATION = '23P01';

    public function __construct(private readonly PricingService $pricing) {}

    /**
     * Tient les dates le temps du paiement.
     *
     * Trois protections superposées, du plus faible au plus fort :
     *   1. les règles métier ci-dessous, qui donnent des messages utiles ;
     *   2. un verrou `SELECT … FOR UPDATE` sur la villa, qui sérialise les
     *      tentatives concurrentes portant sur le même bien ;
     *   3. la contrainte d'exclusion PostgreSQL, qui a le dernier mot.
     *
     * Les deux premières servent l'ergonomie. Seule la troisième garantit
     * qu'aucune double réservation ne peut exister, quel que soit le nombre de
     * processus PHP.
     *
     * @throws BookingNotAllowedException|DatesUnavailableException
     */
    public function hold(Property $property, User $customer, string $checkin, string $checkout, int $guests): Booking
    {
        return DB::transaction(function () use ($property, $customer, $checkin, $checkout, $guests) {
            // Relit la villa sous verrou : ses tarifs et sa capacité ne peuvent
            // plus changer entre la validation et l'écriture.
            /** @var Property $locked */
            $locked = Property::query()->whereKey($property->getKey())->lockForUpdate()->firstOrFail();

            $this->assertBookable($locked, $checkin, $checkout, $guests);

            // Le prix est recalculé ici, à partir de la base. Un total venu du
            // navigateur n'est jamais une source.
            $quote = $this->pricing->quote($locked, $checkin, $checkout, $guests);

            $booking = Booking::create([
                'reference' => BookingReference::next(),
                'property_id' => $locked->id,
                'user_id' => $customer->id,
                'checkin_date' => $quote->checkin,
                'checkout_date' => $quote->checkout,
                'nights' => $quote->nightCount,
                'guests_count' => $guests,
                'status' => BookingStatus::Pending,
                'nightly_subtotal' => $quote->nightlySubtotal,
                'cleaning_fee' => $quote->cleaningFee,
                'service_fee' => $quote->serviceFee,
                'discount_total' => $quote->discount,
                'total_amount' => $quote->total,
                'security_deposit' => $quote->securityDeposit,
                'currency' => Money::CURRENCY,
                'price_breakdown' => $quote->toArray(),
                'hold_expires_at' => now()->addMinutes($this->holdMinutes()),
            ]);

            $this->blockDates($booking);

            return $booking;
        });
    }

    /**
     * Confirme une réservation payée.
     *
     * Fige le taux de commission au moment de la confirmation : une
     * modification ultérieure du taux ne doit jamais réécrire l'historique.
     */
    public function confirm(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $this->assertTransition($booking, BookingStatus::Confirmed);

            $rate = (string) Setting::get('platform.commission_rate', 10);
            $commission = $booking->total_amount->percentage($rate);
            $payout = $booking->total_amount->minus($commission);

            $booking->update([
                'status' => BookingStatus::Confirmed,
                'confirmed_at' => now(),
                'hold_expires_at' => null,
                'commission_rate' => $rate,
                'commission_amount' => $commission,
                'owner_payout_amount' => $payout,
            ]);

            Commission::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'property_owner_id' => $booking->property->property_owner_id,
                    'rate' => $rate,
                    'base_amount' => $booking->total_amount,
                    'commission_amount' => $commission,
                    'owner_payout_amount' => $payout,
                    'status' => CommissionStatus::Pending,
                ]
            );

            return $booking->fresh();
        });
    }

    /**
     * Annule une réservation et libère ses dates.
     *
     * La suppression du blocage est le geste essentiel : sans elle, une villa
     * resterait indisponible sur des dates que personne n'occupe.
     */
    public function cancel(Booking $booking, ?User $by = null, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $by, $reason) {
            $this->assertTransition($booking, BookingStatus::Cancelled);

            $booking->availabilityBlock()->delete();

            $booking->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $by?->id,
                'cancellation_reason' => $reason,
            ]);

            $booking->commission()->update(['status' => CommissionStatus::Cancelled]);

            return $booking->fresh();
        });
    }

    /** Clôt un séjour terminé, ce qui ouvre le droit à l'avis. */
    public function complete(Booking $booking): Booking
    {
        $this->assertTransition($booking, BookingStatus::Completed);

        $booking->update(['status' => BookingStatus::Completed, 'completed_at' => now()]);

        return $booking->fresh();
    }

    /**
     * Libère les tenues de dates non payées et arrivées à échéance.
     *
     * Sans ce ménage, un panier abandonné gèlerait un calendrier indéfiniment.
     *
     * @return int nombre de réservations libérées
     */
    public function expireStaleHolds(): int
    {
        $expired = 0;

        Booking::query()->expiredHolds()->each(function (Booking $booking) use (&$expired) {
            $this->cancel($booking, reason: __('Paiement non reçu dans le délai imparti.'));
            $expired++;
        });

        return $expired;
    }

    /** Passe en « terminée » toute réservation confirmée dont le départ est passé. */
    public function completeFinishedStays(): int
    {
        $completed = 0;

        Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->whereDate('checkout_date', '<', now()->toDateString())
            ->each(function (Booking $booking) use (&$completed) {
                $this->complete($booking);
                $completed++;
            });

        return $completed;
    }

    // ------------------------------------------------------------------ interne

    /** @throws BookingNotAllowedException */
    private function assertBookable(Property $property, string $checkin, string $checkout, int $guests): void
    {
        if (! $property->isPublished()) {
            throw new BookingNotAllowedException(__('Cette villa n\'est pas réservable.'));
        }

        $start = Carbon::parse($checkin)->startOfDay();
        $end = Carbon::parse($checkout)->startOfDay();

        if ($start->lt(Carbon::today())) {
            throw new BookingNotAllowedException(__('La date d\'arrivée ne peut pas être dans le passé.'));
        }

        if ($end->lte($start)) {
            throw new BookingNotAllowedException(__('La date de départ doit suivre la date d\'arrivée.'));
        }

        $nights = $start->diffInDays($end);

        if ($nights < $property->min_nights) {
            throw new BookingNotAllowedException(trans_choice(
                'Cette villa se loue à partir de :count nuit.|Cette villa se loue à partir de :count nuits.',
                $property->min_nights,
                ['count' => $property->min_nights],
            ));
        }

        if ($property->max_nights !== null && $nights > $property->max_nights) {
            throw new BookingNotAllowedException(trans_choice(
                'Cette villa se loue pour :count nuit au maximum.|Cette villa se loue pour :count nuits au maximum.',
                $property->max_nights,
                ['count' => $property->max_nights],
            ));
        }

        if ($guests < 1 || $guests > $property->capacity) {
            throw new BookingNotAllowedException(trans_choice(
                'Cette villa accueille :count voyageur.|Cette villa accueille jusqu\'à :count voyageurs.',
                $property->capacity,
                ['count' => $property->capacity],
            ));
        }
    }

    /** @throws DatesUnavailableException */
    private function blockDates(Booking $booking): void
    {
        try {
            AvailabilityBlock::create([
                'property_id' => $booking->property_id,
                'starts_on' => $booking->checkin_date->toDateString(),
                'ends_on' => $booking->checkout_date->toDateString(),
                'reason' => BlockReason::Booking,
                'booking_id' => $booking->id,
            ]);
        } catch (QueryException $e) {
            // 23P01 : la contrainte d'exclusion a refusé le chevauchement.
            // Toute autre erreur SQL remonte telle quelle — la masquer
            // transformerait un incident technique en « dates indisponibles ».
            if (($e->errorInfo[0] ?? null) === self::EXCLUSION_VIOLATION) {
                throw new DatesUnavailableException;
            }

            throw $e;
        }
    }

    /** @throws BookingNotAllowedException */
    private function assertTransition(Booking $booking, BookingStatus $target): void
    {
        if (! $booking->status->canTransitionTo($target)) {
            throw new BookingNotAllowedException(__('Transition impossible : :from → :to.', [
                'from' => $booking->status->label(),
                'to' => $target->label(),
            ]));
        }
    }

    private function holdMinutes(): int
    {
        return (int) Setting::get('booking.hold_minutes', 30);
    }
}
