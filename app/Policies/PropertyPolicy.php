<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Property;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Un propriétaire ne gère que ses propres villas depuis son espace.
 *
 * N'intervient que côté portail propriétaire : les actions d'administration
 * restent fermées par le seul middleware `admin`, sans passer par cette
 * policy.
 */
class PropertyPolicy
{
    public function manage(User $user, Property $property): Response
    {
        return $user->propertyOwner?->id === $property->property_owner_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
