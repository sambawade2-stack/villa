<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;

enum PaymentStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';

    /** Seul un paiement réussi autorise la confirmation d'une réservation. */
    public function isSettled(): bool
    {
        return in_array($this, [self::Succeeded, self::PartiallyRefunded], strict: true);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending, self::Processing => 'warning',
            self::Succeeded => 'success',
            self::Failed => 'danger',
            self::Refunded, self::PartiallyRefunded => 'neutral',
        };
    }
}
