<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\DTO;

final readonly class OutboundMessage
{
    public function __construct(
        /** Destinataire, format international sans séparateurs. */
        public string $to,
        public string $body,
        /** Identifiant interne du message, pour relier la réponse de l'API. */
        public ?int $messageId = null,
    ) {}
}
