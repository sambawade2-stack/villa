<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;

enum UserRole: string
{
    use HasLabel;

    case Admin = 'admin';
    case Customer = 'customer';

    /** Propriétaire disposant d'un accès portail — voir PropertyOwner::$user_id. */
    case Owner = 'owner';
}
