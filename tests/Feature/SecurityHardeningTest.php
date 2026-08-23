<?php

declare(strict_types=1);

use App\Enums\PropertyStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Property;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Constats de l'audit de sécurité du 23 août 2026 — corrections Phase 1
|--------------------------------------------------------------------------
*/

it('ajoute les en-têtes de sécurité à chaque réponse', function () {
    $this->get('/')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});

it('n\'impose pas la CSP stricte en dev/test, pour ne pas casser le rechargement à chaud de Vite', function () {
    expect(app()->environment('local', 'testing'))->toBeTrue();

    $this->get('/')->assertHeaderMissing('Content-Security-Policy');
});

it('force le cookie de session en HTTPS par défaut, sauf override explicite', function () {
    expect(config('session.secure'))->not->toBeFalsy();
});

it('n\'active jamais APP_DEBUG par défaut dans le modèle d\'environnement', function () {
    expect(file_get_contents(base_path('.env.example')))
        ->toContain('APP_DEBUG=false')
        ->not->toContain('APP_DEBUG=true');
});

it('limite le nombre de recherches de villas par minute', function () {
    foreach (range(1, 60) as $i) {
        $this->get(route('villas.index'));
    }

    $this->get(route('villas.index'))->assertStatus(429);
});

/*
|--------------------------------------------------------------------------
| Constats de l'audit de sécurité du 23 août 2026 — corrections Phase 2
|--------------------------------------------------------------------------
*/

it('ignore un rôle fourni en masse sur le modèle utilisateur', function () {
    $user = User::factory()->create();

    $user->fill(['role' => 'admin'])->save();

    expect($user->fresh()->role)->not->toBe(UserRole::Admin);
});

it('ignore un statut ou une vérification fournis en masse sur le modèle villa', function () {
    $property = Property::factory()->create(['status' => PropertyStatus::Draft, 'is_verified' => false]);

    $property->fill(['status' => PropertyStatus::Published, 'is_verified' => true])->save();

    expect($property->fresh())
        ->status->toBe(PropertyStatus::Draft)
        ->is_verified->toBeFalse();
});

it('ignore un montant ou un statut fournis en masse sur le modèle réservation', function () {
    $booking = Booking::factory()->create();
    $originalAmount = $booking->total_amount;

    $booking->fill(['total_amount' => 1, 'commission_amount' => 1])->save();

    expect($booking->fresh()->total_amount->equals($originalAmount))->toBeTrue();
});
