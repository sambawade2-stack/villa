<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\DTO;

final readonly class SendResult
{
    public function __construct(
        public bool $accepted,
        /** Identifiant attribué par le prestataire, quand il y en a un. */
        public ?string $externalId = null,
        public ?string $error = null,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}

    /** @param  array<string, mixed>  $raw */
    public static function ok(?string $externalId = null, array $raw = []): self
    {
        return new self(accepted: true, externalId: $externalId, raw: $raw);
    }

    /** @param  array<string, mixed>  $raw */
    public static function failed(string $error, array $raw = []): self
    {
        return new self(accepted: false, error: $error, raw: $raw);
    }
}
