<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Réinitialisation de mot de passe, en libre-service.
 *
 * Sans ce parcours, un mot de passe oublié n'avait qu'un recours : demander à
 * l'administrateur de le réinitialiser à la main via Tinker.
 */
class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'string', 'email']]);

        Password::sendResetLink($request->only('email'));

        /*
         * Le même message, que l'adresse existe ou non en base. Distinguer les
         * deux cas — « aucun compte pour cette adresse » contre « lien
         * envoyé » — transformerait ce formulaire en outil pour tester quelles
         * adresses sont enregistrées sur le site.
         */
        return back()->with('status', __(
            'Si un compte existe pour cette adresse, un lien de réinitialisation vient d\'être envoyé.'
        ));
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset(
            $data,
            function (User $user, string $password) {
                // Le cast 'hashed' du modèle s'occupe du hachage : assigner le
                // mot de passe en clair ici suffit, comme partout ailleurs.
                $user->forceFill(['password' => $password])->save();

                // Invalide toute session « rester connecté » ouverte avant la
                // réinitialisation — une précaution utile si le mot de passe a
                // été changé parce qu'il avait fuité.
                $user->setRememberToken(str()->random(60));
                $user->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            // Jeton invalide, expiré, ou déjà utilisé : un seul message,
            // pour ne pas révéler laquelle de ces raisons s'applique.
            return back()->withInput($request->only('email'))->withErrors([
                'email' => __('Ce lien de réinitialisation n\'est plus valide. Demandez-en un nouveau.'),
            ]);
        }

        return redirect()->route('login')
            ->with('status', __('Mot de passe modifié. Vous pouvez vous connecter.'));
    }
}
