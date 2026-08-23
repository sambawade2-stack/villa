<?php

declare(strict_types=1);

use App\Enums\BlockReason;
use App\Enums\UserRole;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertyOwner;
use App\Models\User;
use App\Services\Owner\OwnerAccountService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();

    $this->ownerAccount = User::factory()->create(['role' => UserRole::Owner]);
    $this->owner = PropertyOwner::factory()->create(['user_id' => $this->ownerAccount->id]);
    $this->property = Property::factory()->published()->create(['property_owner_id' => $this->owner->id]);

    $this->otherOwnerAccount = User::factory()->create(['role' => UserRole::Owner]);
    $this->otherOwner = PropertyOwner::factory()->create(['user_id' => $this->otherOwnerAccount->id]);
    $this->otherProperty = Property::factory()->published()->create(['property_owner_id' => $this->otherOwner->id]);
});

/*
|--------------------------------------------------------------------------
| Visibilité — chaque propriétaire ne voit que ses propres villas
|--------------------------------------------------------------------------
*/

it('liste uniquement les villas du propriétaire connecté', function () {
    $this->actingAs($this->ownerAccount)->get(route('owner.dashboard'))
        ->assertOk()
        ->assertSee($this->property->name)
        ->assertDontSee($this->otherProperty->name);
});

it('ouvre le détail de sa propre villa', function () {
    $this->actingAs($this->ownerAccount)->get(route('owner.villas.show', $this->property))
        ->assertOk()
        ->assertSee($this->property->name);
});

it('interdit de consulter la villa d\'un autre propriétaire', function () {
    $this->actingAs($this->ownerAccount)->get(route('owner.villas.show', $this->otherProperty))
        ->assertNotFound();
});

it('ferme l\'espace propriétaire à un visiteur non connecté', function () {
    $this->get(route('owner.dashboard'))->assertRedirect(route('login'));
});

/*
|--------------------------------------------------------------------------
| Blocage de dates — jamais une réservation
|--------------------------------------------------------------------------
*/

it('bloque ses propres dates sans jamais créer de réservation', function () {
    $from = Carbon::today()->addMonth()->toDateString();
    $to = Carbon::today()->addMonth()->addDays(4)->toDateString();

    $this->actingAs($this->ownerAccount)
        ->post(route('owner.villas.blocks.store', $this->property), [
            'starts_on' => $from, 'ends_on' => $to, 'note' => 'Séjour familial',
        ])
        ->assertRedirect();

    $block = $this->property->availabilityBlocks()->firstOrFail();

    expect($block->reason)->toBe(BlockReason::OwnerUse)
        ->and($block->created_by)->toBe($this->ownerAccount->id)
        ->and(Booking::count())->toBe(0);
});

it('interdit de bloquer les dates de la villa d\'un autre propriétaire', function () {
    $from = Carbon::today()->addMonth()->toDateString();
    $to = Carbon::today()->addMonth()->addDays(4)->toDateString();

    $this->actingAs($this->ownerAccount)
        ->post(route('owner.villas.blocks.store', $this->otherProperty), [
            'starts_on' => $from, 'ends_on' => $to,
        ])
        ->assertNotFound();

    expect($this->otherProperty->availabilityBlocks()->count())->toBe(0);
});

it('retire son propre blocage', function () {
    $block = AvailabilityBlock::factory()->create([
        'property_id' => $this->property->id,
        'reason' => BlockReason::OwnerUse,
        'created_by' => $this->ownerAccount->id,
    ]);

    $this->actingAs($this->ownerAccount)
        ->delete(route('owner.villas.blocks.destroy', [$this->property, $block]))
        ->assertRedirect();

    expect(AvailabilityBlock::find($block->id))->toBeNull();
});

it('refuse de retirer un blocage posé par l\'administration', function () {
    $block = AvailabilityBlock::factory()->create([
        'property_id' => $this->property->id,
        'reason' => BlockReason::Maintenance,
        'created_by' => $this->admin->id,
    ]);

    $this->actingAs($this->ownerAccount)
        ->delete(route('owner.villas.blocks.destroy', [$this->property, $block]))
        ->assertNotFound();

    expect(AvailabilityBlock::find($block->id))->not->toBeNull();
});

it('refuse de retirer un blocage né d\'une réservation', function () {
    $booking = Booking::factory()->create(['property_id' => $this->property->id]);
    $block = AvailabilityBlock::factory()->forBooking($booking->id)->create([
        'property_id' => $this->property->id,
    ]);

    $this->actingAs($this->ownerAccount)
        ->delete(route('owner.villas.blocks.destroy', [$this->property, $block]))
        ->assertNotFound();

    expect(AvailabilityBlock::find($block->id))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Ouverture de l'accès — OwnerAccountService
|--------------------------------------------------------------------------
*/

it('crée un compte propriétaire sans jamais générer de mot de passe transmis', function () {
    Notification::fake();

    $owner = PropertyOwner::factory()->create(['user_id' => null, 'email' => 'nouveau@example.test']);

    $user = app(OwnerAccountService::class)->grantAccess($owner);

    expect($user->role)->toBe(UserRole::Owner)
        ->and($user->email)->toBe('nouveau@example.test')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($owner->fresh()->user_id)->toBe($user->id)
        // Un mot de passe aléatoire a bien été haché — jamais transmis en clair
        // nulle part, pas même dans la valeur retournée par le service.
        ->and($user->password)->toStartWith('$2y$');

    Notification::assertSentTo($user, ResetPassword::class);
});

it('refuse d\'ouvrir un accès déjà activé', function () {
    expect(fn () => app(OwnerAccountService::class)->grantAccess($this->owner))
        ->toThrow(RuntimeException::class);
});

it('refuse d\'ouvrir un accès sans adresse e-mail', function () {
    $owner = PropertyOwner::factory()->create(['user_id' => null, 'email' => null]);

    expect(fn () => app(OwnerAccountService::class)->grantAccess($owner))
        ->toThrow(RuntimeException::class);
});

it('refuse d\'ouvrir un accès sur une adresse déjà utilisée par un autre compte', function () {
    $owner = PropertyOwner::factory()->create(['user_id' => null, 'email' => $this->ownerAccount->email]);

    expect(fn () => app(OwnerAccountService::class)->grantAccess($owner))
        ->toThrow(RuntimeException::class);
});

it('active l\'accès depuis l\'administration', function () {
    Notification::fake();

    $owner = PropertyOwner::factory()->create(['user_id' => null, 'email' => 'active@example.test']);

    $this->actingAs($this->admin)
        ->post(route('admin.owners.grant-access', $owner))
        ->assertRedirect();

    expect($owner->fresh()->hasAccount())->toBeTrue();
});
