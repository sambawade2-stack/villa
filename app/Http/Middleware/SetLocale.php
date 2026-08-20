<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Choisit la langue de la requête.
 *
 * Ordre de priorité :
 *   1. le paramètre `lang` de l'URL — chaque page a ainsi une adresse stable
 *      par langue, ce qu'exige hreflang : sans elle, une seule URL servirait
 *      deux contenus selon la session, et un moteur n'en indexerait qu'un ;
 *   2. la préférence enregistrée du compte ;
 *   3. le choix de session ;
 *   4. l'en-tête Accept-Language du navigateur ;
 *   5. la langue par défaut.
 *
 * La négociation par le navigateur a longtemps été écartée : la traduction
 * anglaise était partielle, et un parc majoritairement réglé en anglais aurait
 * reçu une version incomplète. Elle est réactivée maintenant que lang/en.json
 * couvre l'interface.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys(config('app.available_locales', []));

        $fromUrl = $request->query('lang');

        if (is_string($fromUrl) && in_array($fromUrl, $available, true)) {
            // Un choix explicite dans l'URL vaut préférence : on le retient
            // pour la navigation qui suit.
            $request->session()->put('locale', $fromUrl);
        }

        $locale = collect([
            is_string($fromUrl) ? $fromUrl : null,
            $request->user()?->locale,
            $request->session()->get('locale'),
            $request->getPreferredLanguage($available),
        ])->first(fn (?string $candidate) => $candidate !== null && in_array($candidate, $available, true));

        app()->setLocale($locale ?? config('app.locale'));

        return $next($request);
    }
}
