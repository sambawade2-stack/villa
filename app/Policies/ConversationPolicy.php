<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Un client n'accède qu'à ses propres fils de conversation.
 *
 * Refus en 404 : l'existence d'une conversation entre deux autres personnes
 * n'a pas à être confirmée par un 403.
 */
class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): Response
    {
        return $conversation->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
