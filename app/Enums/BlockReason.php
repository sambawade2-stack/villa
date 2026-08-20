<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;

enum BlockReason: string
{
    use HasLabel;

    /** Blocage né d'une réservation : libéré automatiquement avec elle. */
    case Booking = 'booking';

    /** Blocage posé à la main par l'administrateur. */
    case Manual = 'manual';

    case Maintenance = 'maintenance';
}
