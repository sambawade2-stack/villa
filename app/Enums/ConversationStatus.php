<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;

enum ConversationStatus: string
{
    use HasLabel;

    case Open = 'open';
    case Closed = 'closed';
}
