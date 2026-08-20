<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->customer = User::factory()->create();
    $this->admin = User::factory()->admin()->create();

    $this->property = Property::factory()->published()->create([
        'base_price' => 200_000, 'weekend_price' => null, 'cleaning_fee' => 25_000,
        'security_deposit' => 100_000, 'capacity' => 8, 'min_nights' => 2,
    ]);
    PropertyImage::factory()->primary()->create(['property_id' => $this->property->id]);

    Setting::put('platform.service_fee_rate', 3.0, 'pricing');
    Setting::put('platform.commission_rate', 10.0, 'commission');
    Setting::flushCache();

    $this->from = Carbon::today()->addMonth()->toDateString();
    $this->to = Carbon::today()->addMonth()->addDays(4)->toDateString();
});

/*
|--------------------------------------------------------------------------
| Parcours complet
|--------------------------------------------------------------------------
*/

it('mène le client de la fiche villa à la confirmation', function () {
    // 1. Le client réserve depuis la fiche.
    $this->actingAs($this->customer)
        ->post(route('bookings.store', $this->property), [
            'checkin' => $this->from, 'checkout' => $this->to, 'guests' => 4,
        ])
        ->assertRedirect();

    $booking = Booking::firstOrFail();

    expect($booking->status)->toBe(BookingStatus::Pending)
        ->and($booking->availabilityBlock)->not->toBeNull();

    // 2. Il accède au paiement.
    $this->actingAs($this->customer)
        ->get(route('bookings.checkout', $booking))
        ->assertOk()
        ->assertSee($booking->reference)
        ->assertSee('Virement, Wave ou Orange Money');

    // 3. Il valide sa demande : le paiement est initié, mais rien n'est confirmé.
    $this->actingAs($this->customer)
        ->post(route('bookings.pay', $booking), ['gateway' => 'manual'])
        ->assertRedirect(route('bookings.show', $booking));

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Pending)
        ->and($booking->payment->status)->toBe(PaymentStatus::Processing);

    // 4. L'administrateur constate le règlement.
    $this->actingAs($this->admin)
        ->post(route('admin.bookings.confirm-payment', $booking), ['note' => 'Wave 12345'])
        ->assertRedirect();

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->payment->status)->toBe(PaymentStatus::Succeeded)
        ->and($booking->confirmed_at)->not->toBeNull()
        ->and($booking->hold_expires_at)->toBeNull()
        // La commission n'existe qu'après règlement constaté.
        ->and($booking->commission)->not->toBeNull()
        ->and($booking->commission->commission_amount->amount)
        ->toBe((int) round($booking->total_amount->amount * 0.10));
});

it('trace quel administrateur a constaté le règlement', function () {
    $this->actingAs($this->customer)->post(route('bookings.store', $this->property), [
        'checkin' => $this->from, 'checkout' => $this->to, 'guests' => 2,
    ]);
    $booking = Booking::firstOrFail();
    $this->actingAs($this->customer)->post(route('bookings.pay', $booking), ['gateway' => 'manual']);

    $this->actingAs($this->admin)->post(route('admin.bookings.confirm-payment', $booking), ['note' => 'Wave 999']);

    $payload = $booking->fresh()->payment->payload;

    expect($payload['confirmed_by'])->toBe($this->admin->id)
        ->and($payload['note'])->toBe('Wave 999');
});

it('ne confirme jamais deux fois le même règlement', function () {
    $this->actingAs($this->customer)->post(route('bookings.store', $this->property), [
        'checkin' => $this->from, 'checkout' => $this->to, 'guests' => 2,
    ]);
    $booking = Booking::firstOrFail();
    $this->actingAs($this->customer)->post(route('bookings.pay', $booking), ['gateway' => 'manual']);

    $this->actingAs($this->admin)->post(route('admin.bookings.confirm-payment', $booking), []);
    $this->actingAs($this->admin)->post(route('admin.bookings.confirm-payment', $booking), [])
        ->assertSessionHas('error');

    // Une seule commission, pas deux.
    expect($booking->property->owner->commissions()->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Ce que le client ne peut pas faire
|--------------------------------------------------------------------------
*/

it('exige d\'être connecté pour réserver', function () {
    $this->post(route('bookings.store', $this->property), [
        'checkin' => $this->from, 'checkout' => $this->to, 'guests' => 2,
    ])->assertRedirect(route('login'));

    expect(Booking::count())->toBe(0);
});

it('ignore un montant envoyé depuis le formulaire', function () {
    $this->actingAs($this->customer)->post(route('bookings.store', $this->property), [
        'checkin' => $this->from, 'checkout' => $this->to, 'guests' => 4,
        // Tentative de fixer soi-même le prix.
        'total_amount' => 1, 'nightly_subtotal' => 1, 'service_fee' => 0,
    ]);

    $booking = Booking::firstOrFail();

    expect($booking->total_amount->amount)->toBeGreaterThan(800_000);
});

it('interdit de consulter la réservation d\'un autre client', function () {
    $this->actingAs($this->customer)->post(route('bookings.store', $this->property), [
        'checkin' => $this->from, 'checkout' => $this->to, 'guests' => 2,
    ]);
    $booking = Booking::firstOrFail();

    $this->actingAs(User::factory()->create())
        ->get(route('bookings.show', $booking))
        ->assertNotFound();
});

it('interdit de payer la réservation d\'un autre client', function () {
    $this->actingAs($this->customer)->post(route('bookings.store', $this->property), [
        'checkin' => $this->from, 'checkout' => $this->to, 'guests' => 2,
    ]);
    $booking = Booking::firstOrFail();

    $this->actingAs(User::factory()->create())
        ->post(route('bookings.pay', $booking), ['gateway' => 'manual'])
        ->assertNotFound();
});

it('refuse une seconde réservation sur les mêmes dates, avec un message clair', function () {
    $this->actingAs($this->customer)->post(route('bookings.store', $this->property), [
        'checkin' => $this->from, 'checkout' => $this->to, 'guests' => 2,
    ]);

    $this->actingAs(User::factory()->create())
        ->from(route('villas.show', $this->property))
        ->post(route('bookings.store', $this->property), [
            'checkin' => $this->from, 'checkout' => $this->to, 'guests' => 2,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Booking::count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Expiration et annulation
|--------------------------------------------------------------------------
*/

it('libère les dates et prévient quand le délai est dépassé', function () {
    $this->actingAs($this->customer)->post(route('bookings.store', $this->property), [
        'checkin' => $this->from, 'checkout' => $this->to, 'guests' => 2,
    ]);
    $booking = Booking::firstOrFail();
    $booking->forceFill(['hold_expires_at' => now()->subMinute()])->saveQuietly();

    $this->actingAs($this->customer)
        ->get(route('bookings.checkout', $booking))
        ->assertRedirect(route('villas.show', $this->property))
        ->assertSessionHas('error');

    expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and(AvailabilityBlock::count())->toBe(0);
});

it('laisse le client annuler et libère aussitôt les dates', function () {
    $this->actingAs($this->customer)->post(route('bookings.store', $this->property), [
        'checkin' => $this->from, 'checkout' => $this->to, 'guests' => 2,
    ]);
    $booking = Booking::firstOrFail();

    $this->actingAs($this->customer)
        ->post(route('bookings.cancel', $booking))
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and(AvailabilityBlock::count())->toBe(0);
});

it('retire la villa des résultats une fois les dates tenues', function () {
    $this->get(route('villas.index', ['checkin' => $this->from, 'checkout' => $this->to]))
        ->assertOk()->assertSee($this->property->name);

    $this->actingAs($this->customer)->post(route('bookings.store', $this->property), [
        'checkin' => $this->from, 'checkout' => $this->to, 'guests' => 2,
    ]);

    $this->get(route('villas.index', ['checkin' => $this->from, 'checkout' => $this->to]))
        ->assertOk()->assertDontSee($this->property->name);
});

it('affiche les réservations du client dans son espace', function () {
    $this->actingAs($this->customer)->post(route('bookings.store', $this->property), [
        'checkin' => $this->from, 'checkout' => $this->to, 'guests' => 2,
    ]);

    $this->actingAs($this->customer)->get(route('bookings.index'))
        ->assertOk()
        ->assertSee($this->property->name)
        ->assertSee(Booking::first()->reference);
});
