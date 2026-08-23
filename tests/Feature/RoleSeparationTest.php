<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Exceptions\BookingNotAllowedException;
use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertyOwner;
use App\Models\User;
use App\Services\Booking\BookingService;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| Séparation des rôles
|--------------------------------------------------------------------------
|
| Un compte est administrateur, client, ou propriétaire — jamais deux à la
| fois. La frontière se tient à trois niveaux : le schéma (un seul rôle par
| compte), les routes (middlewares), et le domaine métier (BookingService).
| Chacun est vérifié ici, car un seul des trois suffirait à être contourné.
|
*/

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();

    $this->ownerAccount = User::factory()->create(['role' => UserRole::Owner]);
    PropertyOwner::factory()->create(['user_id' => $this->ownerAccount->id]);

    $this->property = Property::factory()->published()->create(['min_nights' => 1, 'capacity' => 6]);
    $this->from = Carbon::today()->addMonth()->toDateString();
    $this->to = Carbon::today()->addMonth()->addDays(3)->toDateString();
});

/*
|--------------------------------------------------------------------------
| Schéma
|--------------------------------------------------------------------------
*/

it('n\'attribue qu\'un seul rôle par compte', function () {
    expect($this->admin->role)->toBe(UserRole::Admin)
        ->and($this->customer->role)->toBe(UserRole::Customer)
        ->and($this->ownerAccount->role)->toBe(UserRole::Owner)
        ->and($this->admin->isCustomer())->toBeFalse()
        ->and($this->customer->isAdmin())->toBeFalse()
        ->and($this->customer->isOwner())->toBeFalse()
        ->and($this->ownerAccount->isCustomer())->toBeFalse();
});

it('sépare les deux populations dans les requêtes', function () {
    expect(User::admins()->pluck('id'))->toContain($this->admin->id)
        ->not->toContain($this->customer->id)
        ->and(User::customers()->pluck('id'))->toContain($this->customer->id)
        ->not->toContain($this->admin->id);
});

it('ne laisse pas s\'inscrire avec le rôle administrateur', function () {
    $this->post(route('register'), [
        'first_name' => 'Tentative', 'last_name' => 'Escalade',
        'email' => 'escalade@example.test',
        'password' => 'Zk92WqhsFr2026', 'password_confirmation' => 'Zk92WqhsFr2026',
        'role' => UserRole::Admin->value,
    ]);

    expect(User::where('email', 'escalade@example.test')->first()->role)->toBe(UserRole::Customer);
});

/*
|--------------------------------------------------------------------------
| Le client ne franchit pas la porte de l'administration
|--------------------------------------------------------------------------
*/

it('renvoie 404 au client sur tous les écrans d\'administration', function (string $path) {
    $this->actingAs($this->customer)->get($path)->assertNotFound();
})->with([
    '/admin',
    '/admin/villas',
    '/admin/reservations',
    '/admin/proprietaires',
    '/admin/clients',
    '/admin/messages',
    '/admin/avis',
    '/admin/parametres',
]);

it('refuse au client les actions d\'administration', function () {
    $booking = app(BookingService::class)->hold($this->property, $this->customer, $this->from, $this->to, 2);

    $this->actingAs($this->customer)
        ->post(route('admin.bookings.confirm-payment', $booking))
        ->assertNotFound();

    expect($booking->fresh()->isPending())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| L'administrateur n'entre pas dans l'espace voyageur
|--------------------------------------------------------------------------
*/

it('renvoie l\'administrateur vers son tableau de bord depuis l\'espace voyageur', function (string $path) {
    $this->actingAs($this->admin)->get($path)
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('error');
})->with([
    '/reservations',
    '/favoris',
    '/messages',
    '/notifications',
]);

it('empêche un administrateur de réserver, jusque dans le service', function () {
    // La route est déjà fermée ; on vérifie ici que le domaine l'est aussi,
    // pour qu'aucun appel interne ne puisse contourner la règle.
    expect(fn () => app(BookingService::class)->hold(
        $this->property, $this->admin, $this->from, $this->to, 2,
    ))->toThrow(BookingNotAllowedException::class);

    expect(Booking::count())->toBe(0);
});

it('bloque aussi la réservation par la route', function () {
    $this->actingAs($this->admin)
        ->post(route('bookings.store', $this->property), [
            'checkin' => $this->from, 'checkout' => $this->to, 'guests' => 2,
        ])
        ->assertRedirect(route('admin.dashboard'));

    expect(Booking::count())->toBe(0);
});

it('empêche un administrateur de mettre une villa en favori', function () {
    $this->actingAs($this->admin)
        ->post(route('favorites.toggle', $this->property))
        ->assertRedirect(route('admin.dashboard'));

    expect($this->admin->favorites()->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Le propriétaire ne franchit ni l'administration, ni l'espace voyageur
|--------------------------------------------------------------------------
*/

it('renvoie 404 au propriétaire sur les écrans d\'administration', function () {
    $this->actingAs($this->ownerAccount)->get('/admin')->assertNotFound();
});

it('renvoie le propriétaire vers son espace depuis l\'espace voyageur', function (string $path) {
    $this->actingAs($this->ownerAccount)->get($path)
        ->assertRedirect(route('owner.dashboard'))
        ->assertSessionHas('error');
})->with(['/reservations', '/favoris', '/messages', '/notifications']);

it('renvoie le client et l\'administrateur hors de l\'espace propriétaire', function () {
    $this->actingAs($this->customer)->get(route('owner.dashboard'))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error');

    $this->actingAs($this->admin)->get(route('owner.dashboard'))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error');
});

it('empêche un propriétaire de réserver, jusque dans le service', function () {
    expect(fn () => app(BookingService::class)->hold(
        $this->property, $this->ownerAccount, $this->from, $this->to, 2,
    ))->toThrow(BookingNotAllowedException::class);

    expect(Booking::count())->toBe(0);
});

it('bloque aussi la réservation du propriétaire par la route', function () {
    $this->actingAs($this->ownerAccount)
        ->post(route('bookings.store', $this->property), [
            'checkin' => $this->from, 'checkout' => $this->to, 'guests' => 2,
        ])
        ->assertRedirect(route('owner.dashboard'));

    expect(Booking::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Ce que les rôles partagent
|--------------------------------------------------------------------------
*/

it('donne à chaque rôle son propre écran de notifications', function () {
    // Elles portent des références de réservation et des montants : celles de
    // l'administration n'ont rien à faire dans le décor du site public.
    $this->actingAs($this->customer)->get(route('notifications.index'))->assertOk();
    $this->actingAs($this->customer)->get(route('admin.notifications.index'))->assertNotFound();

    $this->actingAs($this->admin)->get(route('admin.notifications.index'))->assertOk();
    $this->actingAs($this->admin)->get(route('notifications.index'))
        ->assertRedirect(route('admin.dashboard'));
});

it('laisse les trois rôles parcourir le site public', function () {
    foreach ([$this->customer, $this->admin, $this->ownerAccount] as $user) {
        $this->actingAs($user)->get(route('home'))->assertOk();
        $this->actingAs($user)->get(route('villas.index'))->assertOk();
    }
});

/*
|--------------------------------------------------------------------------
| Le jeu de démonstration respecte la séparation
|--------------------------------------------------------------------------
*/

it('ne crée jamais deux comptes partageant la même adresse', function () {
    User::factory()->count(5)->create();
    User::factory()->admin()->create();

    $duplicates = User::query()
        ->selectRaw('email, count(distinct role) as roles')
        ->groupBy('email')
        ->havingRaw('count(distinct role) > 1')
        ->count();

    expect($duplicates)->toBe(0);
});
