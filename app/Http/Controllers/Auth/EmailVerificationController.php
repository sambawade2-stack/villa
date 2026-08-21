<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Vérification de l'adresse e-mail à l'inscription.
 *
 * Sans elle, n'importe qui pouvait créer un compte avec une adresse qu'il ne
 * contrôle pas — les confirmations de réservation et les instructions de
 * règlement partiraient alors dans le vide, ou vers la boîte de quelqu'un
 * d'autre.
 */
class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('home')
            : view('auth.verify-email');
    }

    /**
     * Lien cliqué depuis le courriel.
     *
     * La validité vient de deux endroits : la signature de l'URL (middleware
     * `signed`, expire après config('auth.verification.expire')) et
     * EmailVerificationRequest, qui vérifie en plus que le hash de l'adresse
     * correspond bien à l'utilisateur connecté — pas seulement que l'URL n'a
     * pas été altérée.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        $request->fulfill();

        return redirect()->route('home')
            ->with('status', __('Adresse e-mail vérifiée. Merci !'));
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', __('Un nouveau lien de vérification vient d\'être envoyé.'));
    }
}
