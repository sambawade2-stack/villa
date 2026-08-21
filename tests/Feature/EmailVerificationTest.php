<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Destination;
use App\Models\Property;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

/*
|--------------------------------------------------------------------------
| Envoi à l'inscription
|--------------------------------------------------------------------------
*/

it('envoie un courriel de vérification à l\'inscription', function () {
    Notification::fake();

    $this->post(route('register'), [
        'first_name' => 'Awa', 'last_name' => 'Sow', 'email' => 'awa@example.test',
        'password' => 'motdepasse1', 'password_confirmation' => 'motdepasse1',
    ]);

    $user = User::where('email', 'awa@example.test')->firstOrFail();

    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});

/*
|--------------------------------------------------------------------------
| Page d'attente
|--------------------------------------------------------------------------
*/

it('affiche la page d\'attente à un compte non vérifié', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Vérifiez votre adresse e-mail');
});

it('passe directement un compte déjà vérifié', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('verification.notice'))
        ->assertRedirect(route('home'));
});

/*
|--------------------------------------------------------------------------
| Renvoi
|--------------------------------------------------------------------------
*/

it('renvoie le lien de vérification', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->post(route('verification.send'))
        ->assertRedirect()
        ->assertSessionHas('status');

    Notification::assertSentTo($user, VerifyEmail::class);
});

/*
|--------------------------------------------------------------------------
| Clic sur le lien
|--------------------------------------------------------------------------
*/

it('vérifie l\'adresse avec un lien signé valide', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($url)
        ->assertRedirect(route('home'))
        ->assertSessionHas('status');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('refuse un lien altéré', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    // On change juste l'id après coup : la signature ne correspond plus.
    $tampered = str_replace((string) $user->id, (string) ($user->id + 999), $url);

    $this->actingAs($user)->get($tampered)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('refuse un lien expiré', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->subMinute(), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('refuse un lien dont le hachage ne correspond pas à l\'adresse du compte', function () {
    $user = User::factory()->unverified()->create();

    // Signature valide, mais calculée pour une autre adresse que celle du compte.
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1('quelquun-dautre@example.test'),
    ]);

    $this->actingAs($user)->get($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('interdit de vérifier le compte d\'un autre utilisateur', function () {
    $target = User::factory()->unverified()->create();
    $attacker = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $target->id,
        'hash' => sha1($target->email),
    ]);

    // Connecté en tant qu'attaquant, avec un lien signé pour la cible.
    $this->actingAs($attacker)->get($url)->assertForbidden();

    expect($target->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('exige une connexion pour vérifier', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->get($url)->assertRedirect(route('login'));

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| La barrière sur la réservation
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    $this->destination = Destination::factory()->create();
    $this->property = Property::factory()->published()->create([
        'destination_id' => $this->destination->id, 'min_nights' => 1,
    ]);
    $this->from = Carbon::today()->addMonth()->toDateString();
    $this->to = Carbon::today()->addMonth()->addDays(3)->toDateString();
});

it('empêche un compte non vérifié de réserver', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('bookings.store', $this->property), [
            'checkin' => $this->from, 'checkout' => $this->to, 'guests' => 2,
        ])
        ->assertRedirect(route('verification.notice'));

    expect(Booking::count())->toBe(0);
});

it('laisse un compte vérifié réserver normalement', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('bookings.store', $this->property), [
            'checkin' => $this->from, 'checkout' => $this->to, 'guests' => 2,
        ])
        ->assertRedirect();

    expect(Booking::count())->toBe(1);
});
