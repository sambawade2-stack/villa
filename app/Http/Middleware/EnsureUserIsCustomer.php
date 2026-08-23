<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve l'espace client aux clients.
 *
 * Un administrateur n'est pas un voyageur : il gère les réservations des
 * autres depuis /admin, il n'en a pas à lui. Un propriétaire n'en est pas un
 * non plus : il suit ses propres villas depuis /proprietaire. Les laisser
 * entrer ici leur montrerait des listes vides et, surtout, brouillerait la
 * frontière entre exploiter la plateforme et l'utiliser.
 *
 * Redirection plutôt que 404 : contrairement à l'administration, l'existence
 * de l'espace client n'a rien de confidentiel.
 */
class EnsureUserIsCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.dashboard')
                ->with('error', __('L\'espace client est réservé aux voyageurs. Gérez les réservations depuis l\'administration.'));
        }

        if ($request->user()?->isOwner()) {
            return redirect()->route('owner.dashboard')
                ->with('error', __('L\'espace client est réservé aux voyageurs. Suivez vos villas depuis votre espace propriétaire.'));
        }

        return $next($request);
    }
}
