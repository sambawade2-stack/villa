<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Contracts;

use App\Services\WhatsApp\DTO\InboundMessage;
use App\Services\WhatsApp\DTO\OutboundMessage;
use App\Services\WhatsApp\DTO\SendResult;
use Illuminate\Http\Request;

/**
 * Passerelle WhatsApp.
 *
 * Une interface, plusieurs implémentations résolues par configuration : le
 * domaine métier ignore laquelle est active, exactement comme pour les
 * passerelles de paiement.
 */
interface WhatsAppGateway
{
    public function name(): string;

    /** La passerelle est-elle réellement utilisable (identifiants présents) ? */
    public function isConfigured(): bool;

    public function send(OutboundMessage $message): SendResult;

    /** Vérifie l'authenticité d'un appel entrant avant de le traiter. */
    public function verifyWebhook(Request $request): bool;

    /**
     * Extrait les messages entrants d'une notification.
     *
     * @return list<InboundMessage>
     */
    public function parseWebhook(Request $request): array;
}
