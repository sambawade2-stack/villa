<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as Notifications;

/**
 * Aiguillage des notifications.
 *
 * Concentre deux choses ici plutôt que de les disséminer dans les services :
 * qui sont les destinataires côté administration, et le fait qu'une
 * notification ne doit jamais faire échouer l'action qui l'a déclenchée.
 */
class Notifier
{
    /** @return Collection<int, User> */
    public function admins(): Collection
    {
        return User::query()->where('role', UserRole::Admin)->get();
    }

    /**
     * Notifie l'administration.
     *
     * Les erreurs sont avalées et journalisées : une réservation valide ne doit
     * pas être perdue parce qu'un serveur SMTP est injoignable.
     */
    public function toAdmins(Notification $notification): void
    {
        $this->safely(fn () => Notifications::send($this->admins(), $notification));
    }

    public function to(?User $user, Notification $notification): void
    {
        if ($user === null) {
            return;
        }

        $this->safely(fn () => $user->notify($notification));
    }

    private function safely(callable $send): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            logger()->warning('Notification non envoyée', ['error' => $e->getMessage()]);
        }
    }
}
