<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;

enum CommissionStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case Settled = 'settled';
    case Cancelled = 'cancelled';
}
