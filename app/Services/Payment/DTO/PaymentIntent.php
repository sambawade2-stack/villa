<?php

declare(strict_types=1);

namespace App\Services\Payment\DTO;

/**
 * Résultat de l'initialisation d'un paiement.
 *
 * `redirectUrl` est nul pour les règlements hors ligne : il n'y a alors nulle
 * part où envoyer le client, l'attente se joue côté administration.
 */
final readonly class PaymentIntent
{
    public function __construct(
        public bool $accepted,
        public ?string $redirectUrl = null,
        public ?string $reference = null,
        public ?string $error = null,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}

    /** @param array<string, mixed> $raw */
    public static function offline(string $reference, array $raw = []): self
    {
        return new self(accepted: true, reference: $reference, raw: $raw);
    }

    /** @param array<string, mixed> $raw */
    public static function redirect(string $url, ?string $reference = null, array $raw = []): self
    {
        return new self(accepted: true, redirectUrl: $url, reference: $reference, raw: $raw);
    }

    public static function failed(string $error): self
    {
        return new self(accepted: false, error: $error);
    }
}
