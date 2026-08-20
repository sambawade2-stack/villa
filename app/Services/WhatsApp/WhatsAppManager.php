<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\Contracts\WhatsAppGateway;
use App\Services\WhatsApp\Gateways\CloudApiGateway;
use App\Services\WhatsApp\Gateways\LogGateway;
use InvalidArgumentException;

/**
 * Résout la passerelle WhatsApp active.
 *
 * Retombe sur le journal si « cloud » est demandé sans identifiants : mieux
 * vaut une trace explicite qu'une exception au milieu d'un envoi de message.
 */
class WhatsAppManager
{
    public function gateway(?string $name = null): WhatsAppGateway
    {
        $name ??= (string) config('whatsapp.driver', 'log');

        $gateway = match ($name) {
            'log' => new LogGateway,
            'cloud' => new CloudApiGateway,
            default => throw new InvalidArgumentException("Passerelle WhatsApp inconnue : {$name}."),
        };

        if (! $gateway->isConfigured()) {
            return new LogGateway;
        }

        return $gateway;
    }

    /** La passerelle configurée envoie-t-elle réellement des messages ? */
    public function sendsForReal(): bool
    {
        return $this->gateway()->name() !== 'log';
    }
}
