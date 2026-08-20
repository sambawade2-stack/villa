<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;

enum UserRole: string
{
    use HasLabel;

    case Admin = 'admin';
    case Customer = 'customer';

    // Le rôle propriétaire viendra en v2, quand PropertyOwner::$user_id sera renseigné.
}
