<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\DTO\PaymentIntent;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Règlement hors ligne : Wave, Orange Money, virement, espèces à l'arrivée.
 *
 * C'est la seule passerelle réellement opérationnelle aujourd'hui, et elle
 * correspond à la pratique dominante sur la Petite Côte. Le client reçoit les
 * coordonnées de règlement ; l'administrateur constate la réception et
 * confirme. Aucun webhook : la confirmation est un geste humain, tracé et
 * attribué à un administrateur nommé.
 */
final class ManualTransferGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'manual';
    }

    public function label(): string
    {
        return __('Virement, Wave ou Orange Money');
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function isOffline(): bool
    {
        return true;
    }

    public function initiate(Payment $payment): PaymentIntent
    {
        return PaymentIntent::offline('MAN-'.Str::upper(Str::random(10)));
    }

    public function verifyWebhook(Request $request): bool
    {
        // Pas de notification serveur : ce mode ne s'appuie sur aucun tiers.
        return false;
    }

    public function parseWebhook(Request $request): ?array
    {
        return null;
    }

    public function refund(Payment $payment, Money $amount): bool
    {
        // Le remboursement est effectué à la main, hors du système. On se
        // contente d'enregistrer l'intention : prétendre l'avoir exécuté serait faux.
        return true;
    }
}
