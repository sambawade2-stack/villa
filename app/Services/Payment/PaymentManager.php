<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Gateways\ManualTransferGateway;
use InvalidArgumentException;

/**
 * Résout la passerelle de paiement active.
 *
 * Seul « manual » est implémenté à ce stade. PayDunya, PayTech et Stripe
 * viendront à l'étape 09 : ils s'ajouteront ici sans que rien d'autre bouge.
 */
class PaymentManager
{
    /** @var array<string, class-string<PaymentGateway>> */
    private const GATEWAYS = [
        'manual' => ManualTransferGateway::class,
    ];

    public function gateway(?string $name = null): PaymentGateway
    {
        $name ??= (string) config('payments.default', 'manual');

        if (! isset(self::GATEWAYS[$name])) {
            throw new InvalidArgumentException("Passerelle de paiement inconnue : {$name}.");
        }

        return app(self::GATEWAYS[$name]);
    }

    /** @return list<PaymentGateway> passerelles réellement utilisables */
    public function available(): array
    {
        return collect(self::GATEWAYS)
            ->map(fn (string $class) => app($class))
            ->filter(fn (PaymentGateway $gateway) => $gateway->isConfigured())
            ->values()
            ->all();
    }
}
