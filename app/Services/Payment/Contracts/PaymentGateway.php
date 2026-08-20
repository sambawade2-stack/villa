<?php

declare(strict_types=1);

namespace App\Services\Payment\Contracts;

use App\Models\Payment;
use App\Services\Payment\DTO\PaymentIntent;
use App\Support\Money;
use Illuminate\Http\Request;

/**
 * Passerelle de paiement.
 *
 * Une interface, plusieurs implémentations résolues par configuration. Le
 * domaine métier ignore laquelle est active : ajouter PayDunya ou Stripe ne
 * touchera ni BookingService ni les contrôleurs.
 */
interface PaymentGateway
{
    public function name(): string;

    public function label(): string;

    /** Les identifiants nécessaires sont-ils présents ? */
    public function isConfigured(): bool;

    /** Le règlement se fait-il hors du site (virement, mobile money manuel) ? */
    public function isOffline(): bool;

    public function initiate(Payment $payment): PaymentIntent;

    public function verifyWebhook(Request $request): bool;

    /** @return array{reference: string, status: string, event_id: ?string}|null */
    public function parseWebhook(Request $request): ?array;

    public function refund(Payment $payment, Money $amount): bool;
}
