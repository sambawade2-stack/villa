<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Un client n'accède qu'à ses propres réservations.
 *
 * Une seule règle sert le détail, le paiement et l'annulation : ces actions
 * ne diffèrent pas par qui peut les déclencher, seulement par ce qu'elles
 * font une fois autorisées. Refus en 404 : l'existence de la réservation
 * d'un autre client n'a pas à être confirmée par un 403.
 */
class BookingPolicy
{
    public function view(User $user, Booking $booking): Response
    {
        return $booking->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
