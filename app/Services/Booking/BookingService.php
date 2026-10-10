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
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingRequested;
use App\Notifications\NewBookingForAdmin;
use App\Services\Notifications\Notifier;
use App\Services\Pricing\PricingService;
use App\Support\BookingReference;
use App\Support\Money;
use App\Support\PostgresErrors;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cycle de vie d'une réservation.
 *
 * Point unique d'écriture pour le statut d'une réservation et pour les
 * blocages de calendrier qui en naissent (reason = booking). Les blocages
 * posés à la main par l'administrateur — entretien, indisponibilité —
 * relèvent d'AvailabilityService, qui partage la même contrainte
 * d'exclusion mais n'a rien à voir avec le cycle de vie d'une réservation.
 */
class BookingService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly Notifier $notifier,
    ) {}

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
        $booking = DB::transaction(function () use ($property, $customer, $checkin, $checkout, $guests) {
            // Relit la villa sous verrou : ses tarifs et sa capacité ne peuvent
            // plus changer entre la validation et l'écriture.
            /** @var Property $locked */
            $locked = Property::query()->whereKey($property->getKey())->lockForUpdate()->firstOrFail();

            $this->assertIsTraveller($customer);
            $this->assertBookable($locked, $checkin, $checkout, $guests);

            // Le prix est recalculé ici, à partir de la base. Un total venu du
            // navigateur n'est jamais une source.
            $quote = $this->pricing->quote($locked, $checkin, $checkout, $guests);

            // 'status' et 'total_amount' ne sont pas mass-assignables :
            // forceCreate est le seul chemin qui puisse les écrire.
            $booking = Booking::forceCreate([
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

        // Après la transaction : une notification n'a aucune raison de retenir
        // un verrou de base, ni de faire échouer une réservation déjà écrite.
        $this->notifier->to($customer, new BookingRequested($booking));
        $this->notifier->toAdmins(new NewBookingForAdmin($booking));

        return $booking;
    }

    /**
     * Confirme une réservation payée.
     *
     * Fige le taux de commission au moment de la confirmation : une
     * modification ultérieure du taux ne doit jamais réécrire l'historique.
     */
    public function confirm(Booking $booking): Booking
    {
        $confirmed = DB::transaction(function () use ($booking) {
            $this->assertTransition($booking, BookingStatus::Confirmed);

            $rate = (string) Setting::get('platform.commission_rate', 10);

            // Le propriétaire ne gagne que sur les nuits : le taux porte sur
            // nightly_subtotal, jamais sur les frais de ménage/service, qui
            // reviennent entièrement à la plateforme. commission_amount se
            // déduit ensuite du total payé plutôt que recalculé en parallèle,
            // pour garantir que payout + commission == total_amount au
            // centime, remise éventuelle comprise (elle réduit la part
            // plateforme, pas celle du propriétaire, qui ne décide jamais
            // d'un code promo).
            $ownerCommission = $booking->nightly_subtotal->percentage($rate);
            $payout = $booking->nightly_subtotal->minus($ownerCommission);
            $commission = $booking->total_amount->minus($payout);

            // 'status', 'commission_rate', 'commission_amount' et
            // 'owner_payout_amount' ne sont pas mass-assignables : forceFill
            // est le seul chemin qui puisse les écrire.
            $booking->forceFill([
                'status' => BookingStatus::Confirmed,
                'confirmed_at' => now(),
                'hold_expires_at' => null,
                'commission_rate' => $rate,
                'commission_amount' => $commission,
                'owner_payout_amount' => $payout,
            ])->save();

            Commission::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'property_owner_id' => $booking->property->property_owner_id,
                    'rate' => $rate,
                    // Contrainte DB commissions_amounts_coherent : base_amount
                    // doit rester la somme (commission + reversé), donc le
                    // total payé — même si le taux, lui, ne s'applique qu'aux
                    // nuits (voir plus haut).
                    'base_amount' => $booking->total_amount,
                    'commission_amount' => $commission,
                    'owner_payout_amount' => $payout,
                    'status' => CommissionStatus::Pending,
                ]
            );

            return $booking->fresh();
        });

        $this->notifier->to($confirmed->user, new BookingConfirmed($confirmed));

        return $confirmed;
    }

    /**
     * Annule une réservation et libère ses dates.
     *
     * La suppression du blocage est le geste essentiel : sans elle, une villa
     * resterait indisponible sur des dates que personne n'occupe.
     */
    public function cancel(Booking $booking, ?User $by = null, ?string $reason = null): Booking
    {
        $cancelled = DB::transaction(function () use ($booking, $by, $reason) {
            $this->assertTransition($booking, BookingStatus::Cancelled);

            $booking->availabilityBlock()->delete();

            // 'status' n'est pas mass-assignable : forceFill est le seul
            // chemin qui puisse l'écrire.
            $booking->forceFill([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $by?->id,
                'cancellation_reason' => $reason,
            ])->save();

            $booking->commission()->update(['status' => CommissionStatus::Cancelled]);

            return $booking->fresh();
        });

        $this->notifier->to($cancelled->user, new BookingCancelled($cancelled, $reason));

        return $cancelled;
    }

    /** Clôt un séjour terminé, ce qui ouvre le droit à l'avis. */
    public function complete(Booking $booking): Booking
    {
        $this->assertTransition($booking, BookingStatus::Completed);

        // 'status' n'est pas mass-assignable : forceFill est le seul chemin
        // qui puisse l'écrire.
        $booking->forceFill(['status' => BookingStatus::Completed, 'completed_at' => now()])->save();

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

    /**
     * Ni un administrateur, ni un propriétaire, n'est un voyageur.
     *
     * L'administrateur gère les réservations des clients ; s'il pouvait en
     * créer à son nom, le chiffre d'affaires et les commissions mélangeraient
     * exploitation et usage. Le propriétaire, lui, bloque ses propres dates
     * via AvailabilityService — un simple blocage de calendrier, jamais une
     * réservation qui lui facturerait sa propre villa. La frontière se tient
     * ici, pas seulement dans les routes.
     *
     * @throws BookingNotAllowedException
     */
    private function assertIsTraveller(User $customer): void
    {
        if ($customer->isAdmin()) {
            throw new BookingNotAllowedException(
                __('Un compte administrateur ne peut pas réserver. Créez la réservation au nom du client.')
            );
        }

        if ($customer->isOwner()) {
            throw new BookingNotAllowedException(
                __('Un compte propriétaire ne peut pas réserver. Bloquez les dates depuis votre espace propriétaire.')
            );
        }
    }

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
            // La contrainte d'exclusion a refusé le chevauchement. Toute autre
            // erreur SQL remonte telle quelle — la masquer transformerait un
            // incident technique en « dates indisponibles ».
            if (PostgresErrors::isExclusionViolation($e)) {
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
