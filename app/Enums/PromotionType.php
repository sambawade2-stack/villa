<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;

enum PromotionType: string
{
    use HasLabel;

    case Percentage = 'percentage';
    case Fixed = 'fixed';
}
