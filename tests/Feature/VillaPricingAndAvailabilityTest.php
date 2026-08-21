<?php

declare(strict_types=1);

use App\Enums\BlockReason;
use App\Enums\BookingStatus;
use App\Models\AvailabilityBlock;
use App\Models\Destination;
use App\Models\PricingRule;
use App\Models\Property;
use App\Models\User;
use App\Services\Booking\BookingService;
use App\Services\Pricing\PricingService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
    $this->destination = Destination::factory()->create();
    $this->property = Property::factory()->published()->create([
        'destination_id' => $this->destination->id,
        'base_price' => 100_000,
        'min_nights' => 1,
    ]);
});

/*
|--------------------------------------------------------------------------
| Tarifs de saison
|--------------------------------------------------------------------------
*/

it('ajoute un tarif de saison', function () {
    $this->actingAs($this->admin)->post(route('admin.villas.pricing.store', $this->property), [
        'label' => 'Haute saison',
        'starts_on' => '2026-12-15',
        'ends_on' => '2027-01-10',
        'price_per_night' => 250_000,
        'priority' => 10,
    ])->assertRedirect();

    $rule = PricingRule::firstOrFail();

    expect($rule->property_id)->toBe($this->property->id)
        ->and($rule->label)->toBe('Haute saison')
        ->and($rule->price_per_night->amount)->toBe(250_000);
});

it('refuse une date de fin antérieure ou égale à la date de début', function () {
    $this->actingAs($this->admin)->post(route('admin.villas.pricing.store', $this->property), [
        'label' => 'Invalide',
        'starts_on' => '2026-12-15',
        'ends_on' => '2026-12-15',
        'price_per_night' => 100_000,
        'priority' => 0,
    ])->assertSessionHasErrors('ends_on');

    expect(PricingRule::count())->toBe(0);
});

it('permet à deux règles de se chevaucher', function () {
    // Documenté comme voulu : « haute saison » et « fêtes » se recouvrent.
    PricingRule::factory()->create([
        'property_id' => $this->property->id,
        'starts_on' => '2026-12-01', 'ends_on' => '2027-01-31', 'priority' => 5,
    ]);

    $this->actingAs($this->admin)->post(route('admin.villas.pricing.store', $this->property), [
        'label' => 'Fêtes', 'starts_on' => '2026-12-20', 'ends_on' => '2027-01-05',
        'price_per_night' => 400_000, 'priority' => 20,
    ])->assertRedirect();

    expect(PricingRule::where('property_id', $this->property->id)->count())->toBe(2);
});

it('modifie un tarif de saison existant', function () {
    $rule = PricingRule::factory()->create(['property_id' => $this->property->id]);

    $this->actingAs($this->admin)->put(route('admin.villas.pricing.update', [$this->property, $rule]), [
        'label' => 'Renommé',
        'starts_on' => '2026-07-01',
        'ends_on' => '2026-08-31',
        'price_per_night' => 180_000,
        'priority' => 3,
    ])->assertRedirect();

    expect($rule->fresh()->label)->toBe('Renommé')
        ->and($rule->fresh()->price_per_night->amount)->toBe(180_000);
});

it('supprime un tarif de saison', function () {
    $rule = PricingRule::factory()->create(['property_id' => $this->property->id]);

    $this->actingAs($this->admin)
        ->delete(route('admin.villas.pricing.destroy', [$this->property, $rule]))
        ->assertRedirect();

    expect(PricingRule::find($rule->id))->toBeNull();
});

it('interdit d\'agir sur le tarif d\'une autre villa', function () {
    $other = Property::factory()->create(['destination_id' => $this->destination->id]);
    $rule = PricingRule::factory()->create(['property_id' => $other->id]);

    $this->actingAs($this->admin)
        ->delete(route('admin.villas.pricing.destroy', [$this->property, $rule]))
        ->assertNotFound();

    expect(PricingRule::find($rule->id))->not->toBeNull();
});

it('ferme la gestion des tarifs à un client', function () {
    $this->actingAs($this->customer)->post(route('admin.villas.pricing.store', $this->property), [
        'label' => 'X', 'starts_on' => '2026-12-01', 'ends_on' => '2026-12-10',
        'price_per_night' => 100_000, 'priority' => 0,
    ])->assertNotFound();

    expect(PricingRule::count())->toBe(0);
});

it('applique réellement le tarif de saison au calcul du prix', function () {
    // La preuve que l'écran ne fait pas que remplir une table : le moteur de
    // tarification en tient compte au calcul d'un devis.
    $this->actingAs($this->admin)->post(route('admin.villas.pricing.store', $this->property), [
        'label' => 'Haute saison',
        'starts_on' => '2026-12-20', 'ends_on' => '2027-01-05',
        'price_per_night' => 300_000, 'priority' => 10,
    ]);

    $quote = app(PricingService::class)->quote(
        $this->property->fresh(), '2026-12-24', '2026-12-26', 2,
    );

    expect($quote->nightlySubtotal->amount)->toBe(300_000 * 2);
});

/*
|--------------------------------------------------------------------------
| Disponibilités — blocage manuel
|--------------------------------------------------------------------------
*/

it('bloque une période', function () {
    $this->actingAs($this->admin)->post(route('admin.villas.blocks.store', $this->property), [
        'starts_on' => '2026-09-10', 'ends_on' => '2026-09-15',
        'reason' => 'manual', 'note' => 'Entretien de la piscine',
    ])->assertRedirect();

    $block = AvailabilityBlock::firstOrFail();

    expect($block->property_id)->toBe($this->property->id)
        ->and($block->reason)->toBe(BlockReason::Manual)
        ->and($block->note)->toBe('Entretien de la piscine')
        ->and($block->created_by)->toBe($this->admin->id);
});

it('accepte le motif maintenance', function () {
    $this->actingAs($this->admin)->post(route('admin.villas.blocks.store', $this->property), [
        'starts_on' => '2026-09-10', 'ends_on' => '2026-09-12', 'reason' => 'maintenance',
    ])->assertRedirect();

    expect(AvailabilityBlock::firstOrFail()->reason)->toBe(BlockReason::Maintenance);
});

it('refuse un motif hors liste', function () {
    $this->actingAs($this->admin)->post(route('admin.villas.blocks.store', $this->property), [
        'starts_on' => '2026-09-10', 'ends_on' => '2026-09-12', 'reason' => 'booking',
    ])->assertSessionHasErrors('reason');

    expect(AvailabilityBlock::count())->toBe(0);
});

it('refuse une fin antérieure ou égale au début, avant même d\'interroger la base', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.villas.edit', $this->property))
        ->post(route('admin.villas.blocks.store', $this->property), [
            'starts_on' => '2026-09-15', 'ends_on' => '2026-09-15', 'reason' => 'manual',
        ])
        ->assertRedirect(route('admin.villas.edit', $this->property))
        ->assertSessionHas('error');

    expect(AvailabilityBlock::count())->toBe(0);
});

it('refuse un blocage qui chevauche un blocage existant', function () {
    AvailabilityBlock::create([
        'property_id' => $this->property->id,
        'starts_on' => '2026-09-10', 'ends_on' => '2026-09-20', 'reason' => BlockReason::Manual,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.villas.blocks.store', $this->property), [
            'starts_on' => '2026-09-15', 'ends_on' => '2026-09-25', 'reason' => 'manual',
        ])
        ->assertSessionHas('error');

    expect(AvailabilityBlock::count())->toBe(1);
});

it('refuse un blocage qui chevauche une réservation en cours', function () {
    app(BookingService::class)->hold(
        $this->property, $this->customer,
        Carbon::today()->addMonth()->toDateString(),
        Carbon::today()->addMonth()->addDays(4)->toDateString(),
        2,
    );

    $this->actingAs($this->admin)
        ->post(route('admin.villas.blocks.store', $this->property), [
            'starts_on' => Carbon::today()->addMonth()->addDays(1)->toDateString(),
            'ends_on' => Carbon::today()->addMonth()->addDays(6)->toDateString(),
            'reason' => 'manual',
        ])
        ->assertSessionHas('error');

    expect(AvailabilityBlock::where('reason', BlockReason::Manual)->count())->toBe(0);
});

it('accepte une arrivée le jour du départ d\'un blocage existant', function () {
    AvailabilityBlock::create([
        'property_id' => $this->property->id,
        'starts_on' => '2026-09-10', 'ends_on' => '2026-09-15', 'reason' => BlockReason::Manual,
    ]);

    $this->actingAs($this->admin)->post(route('admin.villas.blocks.store', $this->property), [
        'starts_on' => '2026-09-15', 'ends_on' => '2026-09-20', 'reason' => 'manual',
    ])->assertRedirect();

    expect(AvailabilityBlock::count())->toBe(2);
});

it('libère un blocage manuel et rend les dates réservables', function () {
    $block = AvailabilityBlock::create([
        'property_id' => $this->property->id,
        'starts_on' => Carbon::today()->addMonth()->toDateString(),
        'ends_on' => Carbon::today()->addMonth()->addDays(5)->toDateString(),
        'reason' => BlockReason::Manual,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.villas.blocks.destroy', [$this->property, $block]))
        ->assertRedirect();

    expect(AvailabilityBlock::find($block->id))->toBeNull();

    // Preuve que la libération est réelle : une réservation passe désormais.
    $booking = app(BookingService::class)->hold(
        $this->property, $this->customer,
        Carbon::today()->addMonth()->toDateString(),
        Carbon::today()->addMonth()->addDays(3)->toDateString(),
        2,
    );

    expect($booking->status)->toBe(BookingStatus::Pending);
});

it('refuse de libérer un blocage né d\'une réservation', function () {
    $booking = app(BookingService::class)->hold(
        $this->property, $this->customer,
        Carbon::today()->addMonth()->toDateString(),
        Carbon::today()->addMonth()->addDays(4)->toDateString(),
        2,
    );
    $block = $booking->availabilityBlock;

    $this->actingAs($this->admin)
        ->delete(route('admin.villas.blocks.destroy', [$this->property, $block]))
        ->assertSessionHas('error');

    expect(AvailabilityBlock::find($block->id))->not->toBeNull();
});

it('interdit d\'agir sur le blocage d\'une autre villa', function () {
    $other = Property::factory()->create(['destination_id' => $this->destination->id]);
    $block = AvailabilityBlock::create([
        'property_id' => $other->id, 'starts_on' => '2026-09-10', 'ends_on' => '2026-09-15',
        'reason' => BlockReason::Manual,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.villas.blocks.destroy', [$this->property, $block]))
        ->assertNotFound();

    expect(AvailabilityBlock::find($block->id))->not->toBeNull();
});

it('ferme le blocage manuel à un client', function () {
    $this->actingAs($this->customer)->post(route('admin.villas.blocks.store', $this->property), [
        'starts_on' => '2026-09-10', 'ends_on' => '2026-09-15', 'reason' => 'manual',
    ])->assertNotFound();

    expect(AvailabilityBlock::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Rendu de l'écran
|--------------------------------------------------------------------------
*/

it('affiche les deux nouveaux onglets sur l\'écran d\'édition', function () {
    PricingRule::factory()->create(['property_id' => $this->property->id, 'label' => 'Saison haute']);
    AvailabilityBlock::create([
        'property_id' => $this->property->id, 'starts_on' => '2026-09-10', 'ends_on' => '2026-09-12',
        'reason' => BlockReason::Maintenance, 'note' => 'Peinture',
    ]);

    $this->actingAs($this->admin)->get(route('admin.villas.edit', $this->property))
        ->assertOk()
        ->assertSee('Tarifs de saison')
        ->assertSee('Saison haute')
        ->assertSee('Bloquer des dates')
        ->assertSee('Peinture');
});
