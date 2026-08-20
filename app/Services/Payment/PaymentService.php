<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\TransactionType;
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

    /** Crée le paiement et lance la passerelle. */
    public function initiate(Booking $booking, string $gatewayName): Payment
    {
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
     */
    public function markPaid(Payment $payment, ?User $confirmedBy = null, ?string $note = null): Payment
    {
        return DB::transaction(function () use ($payment, $confirmedBy, $note) {
            if ($payment->status->isSettled()) {
                // Déjà réglé : on ne confirme pas deux fois, et surtout on ne
                // crée pas une seconde commission.
                return $payment;
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

            $booking = $payment->booking;

            if ($booking->status->canTransitionTo(BookingStatus::Confirmed)) {
                $this->bookings->confirm($booking);
            }

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
