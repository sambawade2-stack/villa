<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ferme l'administration à tout ce qui n'est pas un administrateur.
 *
 * Renvoie 404 plutôt que 403 : l'existence même des écrans d'administration
 * n'a pas à être confirmée à un visiteur.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdmin() ?? false, 404);

        return $next($request);
    }
}
