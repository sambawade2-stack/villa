<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;

enum OwnerStatus: string
{
    use HasLabel;

    case Active = 'active';
    case Inactive = 'inactive';
}
