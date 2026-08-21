<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

/*
|--------------------------------------------------------------------------
| Demande de lien
|--------------------------------------------------------------------------
*/

it('affiche le formulaire de demande', function () {
    $this->get(route('password.request'))->assertOk()->assertSee('Mot de passe oublié');
});

it('envoie un lien à une adresse existante', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'moussa@example.test']);

    $this->post(route('password.email'), ['email' => 'moussa@example.test'])
        ->assertRedirect()
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

it('renvoie le même message qu\'une adresse existe ou non', function () {
    // Distinguer les deux réponses transformerait ce formulaire en outil pour
    // tester quelles adresses sont enregistrées sur le site.
    Notification::fake();

    $known = User::factory()->create(['email' => 'connu@example.test']);

    $this->post(route('password.email'), ['email' => 'connu@example.test']);
    $messageForKnown = session('status');

    $this->post(route('password.email'), ['email' => 'inconnu@example.test']);
    $messageForUnknown = session('status');

    expect($messageForKnown)->not->toBeNull()
        ->and($messageForKnown)->toBe($messageForUnknown);

    // Aucun compte « inconnu@example.test » n'existe : rien ne peut avoir été
    // envoyé pour cette adresse, il n'y a même pas de destinataire à vérifier.
    Notification::assertSentTo($known, ResetPassword::class);
    Notification::assertCount(1);
});

it('limite la cadence des demandes', function () {
    Notification::fake();

    foreach (range(1, 6) as $i) {
        $response = $this->post(route('password.email'), ['email' => "essai{$i}@example.test"]);
    }

    expect($response->status())->toBe(429);
});

/*
|--------------------------------------------------------------------------
| Choix du nouveau mot de passe
|--------------------------------------------------------------------------
*/

it('affiche le formulaire avec le jeton et l\'adresse pré-remplis', function () {
    $this->get(route('password.reset', ['token' => 'un-jeton', 'email' => 'moussa@example.test']))
        ->assertOk()
        ->assertSee('un-jeton', escape: false)
        ->assertSee('moussa@example.test');
});

it('réinitialise le mot de passe avec un jeton valide', function () {
    $user = User::factory()->create(['email' => 'moussa@example.test', 'password' => 'ancien-motdepasse1']);

    $token = app('auth.password.broker')->createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => 'moussa@example.test',
        'password' => 'nouveau-motdepasse1',
        'password_confirmation' => 'nouveau-motdepasse1',
    ])->assertRedirect(route('login'));

    expect(Hash::check('nouveau-motdepasse1', $user->fresh()->password))->toBeTrue()
        ->and(Hash::check('ancien-motdepasse1', $user->fresh()->password))->toBeFalse();
});

it('connecte avec le nouveau mot de passe après réinitialisation', function () {
    $user = User::factory()->create(['email' => 'moussa@example.test', 'password' => 'ancien-motdepasse1']);
    $token = app('auth.password.broker')->createToken($user);

    $this->post(route('password.update'), [
        'token' => $token, 'email' => 'moussa@example.test',
        'password' => 'nouveau-motdepasse1', 'password_confirmation' => 'nouveau-motdepasse1',
    ]);

    $this->post(route('login'), ['email' => 'moussa@example.test', 'password' => 'nouveau-motdepasse1'])
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

it('refuse un jeton invalide', function () {
    $user = User::factory()->create(['email' => 'moussa@example.test', 'password' => 'ancien-motdepasse1']);

    $this->post(route('password.update'), [
        'token' => 'jeton-invente',
        'email' => 'moussa@example.test',
        'password' => 'nouveau-motdepasse1',
        'password_confirmation' => 'nouveau-motdepasse1',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('ancien-motdepasse1', $user->fresh()->password))->toBeTrue();
});

it('refuse un jeton déjà consommé', function () {
    $user = User::factory()->create(['email' => 'moussa@example.test']);
    $token = app('auth.password.broker')->createToken($user);

    $payload = [
        'token' => $token, 'email' => 'moussa@example.test',
        'password' => 'motdepasse1', 'password_confirmation' => 'motdepasse1',
    ];

    $this->post(route('password.update'), $payload)->assertRedirect(route('login'));

    $this->post(route('password.update'), [
        ...$payload, 'password' => 'autremotdepasse1', 'password_confirmation' => 'autremotdepasse1',
    ])->assertSessionHasErrors('email');
});

it('refuse un mot de passe trop court', function () {
    $user = User::factory()->create(['email' => 'moussa@example.test']);
    $token = app('auth.password.broker')->createToken($user);

    $this->post(route('password.update'), [
        'token' => $token, 'email' => 'moussa@example.test',
        'password' => 'abc1', 'password_confirmation' => 'abc1',
    ])->assertSessionHasErrors('password');
});

it('révoque toute session « rester connecté » à la réinitialisation', function () {
    $user = User::factory()->create(['email' => 'moussa@example.test', 'remember_token' => 'ancien-jeton']);
    $token = app('auth.password.broker')->createToken($user);

    $this->post(route('password.update'), [
        'token' => $token, 'email' => 'moussa@example.test',
        'password' => 'nouveau-motdepasse1', 'password_confirmation' => 'nouveau-motdepasse1',
    ]);

    expect($user->fresh()->remember_token)->not->toBe('ancien-jeton');
});

it('consomme le jeton en base après un succès', function () {
    $user = User::factory()->create(['email' => 'moussa@example.test']);
    $token = app('auth.password.broker')->createToken($user);

    expect(DB::table('password_reset_tokens')->where('email', 'moussa@example.test')->exists())->toBeTrue();

    $this->post(route('password.update'), [
        'token' => $token, 'email' => 'moussa@example.test',
        'password' => 'nouveau-motdepasse1', 'password_confirmation' => 'nouveau-motdepasse1',
    ]);

    expect(DB::table('password_reset_tokens')->where('email', 'moussa@example.test')->exists())->toBeFalse();
});
