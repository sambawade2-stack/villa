<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;

enum TransactionType: string
{
    use HasLabel;

    case Initiate = 'initiate';
    case Webhook = 'webhook';
    case Verify = 'verify';
    case Refund = 'refund';
}
