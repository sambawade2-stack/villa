<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\TransactionType;
use App\Exceptions\BookingNotAllowedException;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Services\Booking\BookingService;
use App\Services\Payment\DTO\PaymentIntent;
use Illuminate\Support\Facades\DB;

/**
 * Paiements d'une réservation.
 *
 * Règle cardinale : une réservation ne passe en « confirmée » qu'après
 * constatation du règlement côté serveur. Un retour de navigateur ne confirme
 * jamais rien — il est trivial de le fabriquer.
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentManager $gateways,
        private readonly BookingService $bookings,
    ) {}

    /**
     * Crée le paiement et lance la passerelle.
     *
     * Rejoue la session en cours plutôt que d'en ouvrir une seconde : deux
     * clics sur « payer », ou une requête rejouée, ne doivent pas laisser
     * deux paiements actifs sur la même réservation — surtout le jour où une
     * vraie passerelle facturera réellement chaque session ouverte.
     */
    public function initiate(Booking $booking, string $gatewayName): Payment
    {
        $active = $booking->payments()
            ->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Processing])
            ->first();

        if ($active !== null) {
            return $active;
        }

        $gateway = $this->gateways->gateway($gatewayName);

        return DB::transaction(function () use ($booking, $gateway) {
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'gateway' => $gateway->name(),
                'status' => PaymentStatus::Pending,
                'amount' => $booking->total_amount,
                'currency' => $booking->currency,
            ]);

            $intent = $gateway->initiate($payment);

            $payment->update([
                'status' => $intent->accepted ? PaymentStatus::Processing : PaymentStatus::Failed,
                'provider_reference' => $intent->reference,
                'checkout_url' => $intent->redirectUrl,
                'failed_at' => $intent->accepted ? null : now(),
                'failure_reason' => $intent->error,
            ]);

            $this->trace($payment, TransactionType::Initiate, $intent);

            return $payment->fresh();
        });
    }

    /**
     * Constate un règlement et confirme la réservation.
     *
     * `$confirmedBy` est renseigné pour un règlement hors ligne : on veut savoir
     * quel administrateur a constaté la réception, et quand.
     *
     * @throws BookingNotAllowedException si la réservation ne peut plus être
     *                                    confirmée (déjà annulée — par exemple un délai de paiement
     *                                    dépassé entre-temps — déjà terminée ou remboursée). Constater
     *                                    un règlement sans pouvoir honorer la réservation créerait un
     *                                    paiement « réglé » fantôme, sans réservation en face et sans
     *                                    remboursement déclenché.
     */
    public function markPaid(Payment $payment, ?User $confirmedBy = null, ?string $note = null): Payment
    {
        return DB::transaction(function () use ($payment, $confirmedBy, $note) {
            if ($payment->status->isSettled()) {
                // Déjà réglé : on ne confirme pas deux fois, et surtout on ne
                // crée pas une seconde commission.
                return $payment;
            }

            $booking = $payment->booking;

            if (! $booking->status->canTransitionTo(BookingStatus::Confirmed)) {
                throw new BookingNotAllowedException(__(
                    'Cette réservation est :statut et ne peut plus être confirmée. Contactez le client avant de constater ce règlement : il faudra probablement le rembourser ou le reloger sur d\'autres dates.',
                    ['statut' => mb_strtolower($booking->status->label())]
                ));
            }

            $payment->update([
                'status' => PaymentStatus::Succeeded,
                'paid_at' => now(),
                'payload' => array_filter([
                    'confirmed_by' => $confirmedBy?->id,
                    'confirmed_by_name' => $confirmedBy?->full_name,
                    'note' => $note,
                ]),
            ]);

            $payment->transactions()->create([
                'gateway' => $payment->gateway,
                'type' => TransactionType::Verify,
                'status' => PaymentStatus::Succeeded->value,
                'amount' => $payment->amount,
                'provider_reference' => $payment->provider_reference,
                'occurred_at' => now(),
            ]);

            $this->bookings->confirm($booking);

            return $payment->fresh();
        });
    }

    public function markFailed(Payment $payment, string $reason): Payment
    {
        $payment->update([
            'status' => PaymentStatus::Failed,
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);

        return $payment->fresh();
    }

    private function trace(Payment $payment, TransactionType $type, PaymentIntent $intent): void
    {
        $payment->transactions()->create([
            'gateway' => $payment->gateway,
            'type' => $type,
            'status' => $intent->accepted ? 'accepted' : 'failed',
            'amount' => $payment->amount,
            'provider_reference' => $intent->reference,
            'raw_payload' => $intent->raw,
            'occurred_at' => now(),
        ]);
    }
}
