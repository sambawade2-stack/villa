<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\DTO;

use Illuminate\Support\Carbon;

final readonly class InboundMessage
{
    public function __construct(
        /** Numéro de l'expéditeur, format international sans séparateurs. */
        public string $from,
        public string $body,
        public string $externalId,
        public ?string $profileName = null,
        public ?Carbon $sentAt = null,
    ) {}
}
