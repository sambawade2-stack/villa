<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;

enum PropertyType: string
{
    use HasLabel;

    case Villa = 'villa';
    case Apartment = 'apartment';
    case Residence = 'residence';
    case Duplex = 'duplex';
}
