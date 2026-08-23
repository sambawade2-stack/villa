<?php

declare(strict_types=1);

namespace App\Services\Owner;

use App\Enums\UserRole;
use App\Models\PropertyOwner;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Ouvre l'accès au portail propriétaire.
 *
 * Aucun mot de passe n'est jamais généré puis transmis : le compte naît avec
 * un mot de passe aléatoire inutilisable, et c'est le circuit existant de
 * réinitialisation qui laisse le propriétaire choisir le sien. Personne, pas
 * même l'administrateur qui déclenche l'accès, ne connaît jamais ce mot de
 * passe initial.
 */
class OwnerAccountService
{
    public function grantAccess(PropertyOwner $owner): User
    {
        if ($owner->hasAccount()) {
            throw new RuntimeException('Ce propriétaire dispose déjà d\'un accès.');
        }

        if (blank($owner->email)) {
            throw new RuntimeException('Une adresse e-mail est nécessaire pour ouvrir un accès.');
        }

        if (User::withTrashed()->where('email', $owner->email)->exists()) {
            throw new RuntimeException('Cette adresse e-mail est déjà utilisée par un autre compte.');
        }

        $user = User::forceCreate([
            'first_name' => $owner->first_name,
            'last_name' => $owner->last_name,
            'email' => $owner->email,
            'phone' => $owner->phone,
            'whatsapp' => $owner->whatsapp,
            'password' => Str::password(40),
            'role' => UserRole::Owner,
            'locale' => app()->getLocale(),
            'email_verified_at' => now(),
        ]);

        $owner->update(['user_id' => $user->id]);

        Password::sendResetLink(['email' => $owner->email]);

        return $user;
    }
}
