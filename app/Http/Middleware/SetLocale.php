<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Choisit la langue de la requête.
 *
 * Ordre de priorité : préférence enregistrée du compte, puis choix explicite
 * de session, puis la langue par défaut.
 *
 * La négociation par l'en-tête Accept-Language est délibérément écartée pour
 * l'instant. Le marché est francophone et beaucoup d'appareils y sont livrés
 * réglés en anglais : s'y fier servirait une version anglaise — encore
 * incomplète — à des visiteurs qui ne l'ont pas demandée. Elle sera réactivée
 * à l'étape 11, quand la traduction sera intégrale.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys(config('app.available_locales', []));

        $locale = collect([
            $request->user()?->locale,
            $request->session()->get('locale'),
        ])->first(fn (?string $candidate) => $candidate !== null && in_array($candidate, $available, true));

        app()->setLocale($locale ?? config('app.locale'));

        return $next($request);
    }
}
