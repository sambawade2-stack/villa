<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve l'espace propriétaire aux comptes propriétaires.
 *
 * Redirection plutôt que 404 : l'existence d'un portail propriétaire n'a
 * rien de confidentiel, le site en fait la publicité (« Déposer ma villa »).
 */
class EnsureUserIsOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! ($request->user()?->isOwner() ?? false)) {
            return redirect()->route('home')
                ->with('error', __('Cet espace est réservé aux propriétaires disposant d\'un accès.'));
        }

        return $next($request);
    }
}
