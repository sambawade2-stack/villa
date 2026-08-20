<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

it('affiche le formulaire de connexion', function () {
    $this->get(route('login'))->assertOk()->assertSee('Se connecter');
});

it('connecte un client avec les bons identifiants', function () {
    $user = User::factory()->create(['email' => 'moussa@example.test', 'password' => 'motdepasse1']);

    $this->post(route('login'), ['email' => 'moussa@example.test', 'password' => 'motdepasse1'])
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

it('renvoie un administrateur vers son tableau de bord', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.test', 'password' => 'motdepasse1']);

    $this->post(route('login'), ['email' => 'admin@example.test', 'password' => 'motdepasse1'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin);
});

it('refuse un mauvais mot de passe', function () {
    User::factory()->create(['email' => 'moussa@example.test', 'password' => 'motdepasse1']);

    $this->from(route('login'))
        ->post(route('login'), ['email' => 'moussa@example.test', 'password' => 'incorrect'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('bloque après cinq tentatives infructueuses', function () {
    RateLimiter::clear('login:moussa@example.test|127.0.0.1');
    User::factory()->create(['email' => 'moussa@example.test', 'password' => 'motdepasse1']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login'), ['email' => 'moussa@example.test', 'password' => 'incorrect']);
    }

    // Le bon mot de passe ne passe plus : c'est bien la cadence qui est limitée,
    // pas seulement les échecs.
    $this->post(route('login'), ['email' => 'moussa@example.test', 'password' => 'motdepasse1'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();

    RateLimiter::clear('login:moussa@example.test|127.0.0.1');
});

it('inscrit un nouveau client', function () {
    $this->post(route('register'), [
        'first_name' => 'Awa',
        'last_name' => 'Sow',
        'email' => 'awa@example.test',
        'password' => 'motdepasse1',
        'password_confirmation' => 'motdepasse1',
    ])->assertRedirect(route('home'));

    $user = User::where('email', 'awa@example.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Customer)
        ->and($user->full_name)->toBe('Awa Sow');

    $this->assertAuthenticatedAs($user);
});

it('ne laisse pas s\'inscrire comme administrateur', function () {
    $this->post(route('register'), [
        'first_name' => 'Malicieux',
        'last_name' => 'Utilisateur',
        'email' => 'pirate@example.test',
        'password' => 'motdepasse1',
        'password_confirmation' => 'motdepasse1',
        'role' => 'admin',
    ]);

    expect(User::where('email', 'pirate@example.test')->first()->role)->toBe(UserRole::Customer);
});

it('refuse un mot de passe trop court ou sans chiffre', function (string $password) {
    $this->post(route('register'), [
        'first_name' => 'Awa', 'last_name' => 'Sow', 'email' => 'awa@example.test',
        'password' => $password, 'password_confirmation' => $password,
    ])->assertSessionHasErrors('password');
})->with(['court' => 'abc1', 'sans chiffre' => 'motdepasse']);

it('déconnecte et invalide la session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('home'));

    $this->assertGuest();
});

it('empêche un utilisateur connecté de revoir le formulaire de connexion', function () {
    $this->actingAs(User::factory()->create())->get(route('login'))->assertRedirect();
});

/*
|--------------------------------------------------------------------------
| Langue
|--------------------------------------------------------------------------
*/

it('change de langue et retient le choix', function () {
    $this->from(route('home'))->get(route('locale.switch', 'en'))->assertRedirect(route('home'));

    expect(session('locale'))->toBe('en');
});

it('refuse une langue inconnue', function () {
    $this->get(route('locale.switch', 'de'))->assertNotFound();
});

it('enregistre la langue sur le compte connecté', function () {
    $user = User::factory()->create(['locale' => 'fr']);

    $this->actingAs($user)->from(route('home'))->get(route('locale.switch', 'en'));

    expect($user->fresh()->locale)->toBe('en');
});
