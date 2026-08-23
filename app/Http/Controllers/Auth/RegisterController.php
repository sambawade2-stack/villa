<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()->uncompromised()],
        ]);

        // Le rôle n'est jamais accepté depuis la requête : on ne s'inscrit pas
        // administrateur en ajoutant un champ au formulaire. Il est aussi hors
        // de $fillable — forceCreate est le seul chemin qui puisse l'écrire.
        $user = User::forceCreate([
            ...$data,
            'role' => UserRole::Customer,
            'locale' => app()->getLocale(),
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')
            ->with('status', __('Bienvenue ! Votre compte est créé.'));
    }
}
