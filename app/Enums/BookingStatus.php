<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;

enum BookingStatus: string
{
    use HasLabel;

    /** Dates tenues, paiement en attente. Expire au bout de BOOKING_HOLD_MINUTES. */
    case Pending = 'pending';

    /** Paiement confirmé côté serveur. */
    case Confirmed = 'confirmed';

    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case Refunded = 'refunded';

    /**
     * Transitions autorisées. Toute autre est refusée par BookingService.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Completed, self::Cancelled],
            self::Cancelled => [self::Refunded],
            self::Completed, self::Refunded => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), strict: true);
    }

    /** Les statuts qui immobilisent réellement des dates au calendrier. */
    public function holdsDates(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], strict: true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'success',
            self::Completed => 'info',
            self::Cancelled => 'neutral',
            self::Refunded => 'danger',
        };
    }
}
