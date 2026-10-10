<?php

declare(strict_types=1);

use App\Enums\BlockReason;
use App\Enums\BookingStatus;
use App\Enums\CommissionStatus;
use App\Exceptions\BookingNotAllowedException;
use App\Exceptions\DatesUnavailableException;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use App\Services\Booking\BookingService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->service = app(BookingService::class);
    $this->customer = User::factory()->create();

    $this->property = Property::factory()->published()->create([
        'base_price' => 200_000,
        'weekend_price' => null,
        'cleaning_fee' => 25_000,
        'security_deposit' => 100_000,
        'capacity' => 8,
        'min_nights' => 2,
    ]);

    Setting::put('platform.service_fee_rate', 3.0, 'pricing');
    Setting::put('platform.commission_rate', 10.0, 'commission');
    Setting::put('booking.hold_minutes', 30, 'booking');
    Setting::flushCache();

    $this->from = Carbon::today()->addMonth()->toDateString();
    $this->to = Carbon::today()->addMonth()->addDays(4)->toDateString();
});

/*
|--------------------------------------------------------------------------
| Tenue des dates
|--------------------------------------------------------------------------
*/

it('tient les dates et bloque le calendrier', function () {
    $booking = $this->service->hold($this->property, $this->customer, $this->from, $this->to, 4);

    expect($booking->status)->toBe(BookingStatus::Pending)
        ->and($booking->nights)->toBe(4)
        ->and($booking->hold_expires_at)->not->toBeNull()
        ->and($booking->availabilityBlock)->not->toBeNull()
        ->and($booking->availabilityBlock->reason)->toBe(BlockReason::Booking);
});

it('calcule le prix côté serveur, sans rien recevoir du client', function () {
    $booking = $this->service->hold($this->property, $this->customer, $this->from, $this->to, 4);

    $nightly = 200_000 * 4;
    $service = (int) round($nightly * 0.03);

    expect($booking->nightly_subtotal->amount)->toBe($nightly)
        ->and($booking->cleaning_fee->amount)->toBe(25_000)
        ->and($booking->service_fee->amount)->toBe($service)
        ->and($booking->total_amount->amount)->toBe($nightly + 25_000 + $service)
        // Le détail nuit par nuit est conservé pour justifier le prix plus tard.
        ->and($booking->price_breakdown['nights'])->toHaveCount(4);
});

it('attribue des références uniques et lisibles', function () {
    $first = $this->service->hold($this->property, $this->customer, $this->from, $this->to, 2);

    $other = Property::factory()->published()->create(['min_nights' => 1]);
    $second = $this->service->hold($other, $this->customer, $this->from, $this->to, 2);

    expect($first->reference)->toMatch('/^PCV-\d{4}-\d{6}$/')
        ->and($second->reference)->not->toBe($first->reference);
});

/*
|--------------------------------------------------------------------------
| Double réservation
|--------------------------------------------------------------------------
*/

it('refuse une seconde réservation sur des dates qui se chevauchent', function () {
    $this->service->hold($this->property, $this->customer, $this->from, $this->to, 2);

    $other = User::factory()->create();

    expect(fn () => $this->service->hold(
        $this->property, $other,
        Carbon::parse($this->from)->addDays(2)->toDateString(),
        Carbon::parse($this->to)->addDays(2)->toDateString(),
        2,
    ))->toThrow(DatesUnavailableException::class);

    // La seconde tentative ne doit laisser aucune trace : la transaction
    // entière est annulée, pas seulement le blocage.
    expect(Booking::count())->toBe(1)
        ->and(AvailabilityBlock::count())->toBe(1);
});

it('accepte une arrivée le jour du départ précédent', function () {
    $this->service->hold($this->property, $this->customer, $this->from, $this->to, 2);

    $second = $this->service->hold(
        $this->property, User::factory()->create(),
        $this->to,
        Carbon::parse($this->to)->addDays(3)->toDateString(),
        2,
    );

    expect($second->status)->toBe(BookingStatus::Pending)
        ->and(Booking::count())->toBe(2);
});

it('accepte les mêmes dates sur une autre villa', function () {
    $other = Property::factory()->published()->create(['min_nights' => 1]);

    $this->service->hold($this->property, $this->customer, $this->from, $this->to, 2);
    $this->service->hold($other, $this->customer, $this->from, $this->to, 2);

    expect(Booking::count())->toBe(2);
});

/*
|--------------------------------------------------------------------------
| Règles métier
|--------------------------------------------------------------------------
*/

it('refuse une arrivée dans le passé', function () {
    expect(fn () => $this->service->hold(
        $this->property, $this->customer,
        Carbon::yesterday()->toDateString(),
        Carbon::today()->addDays(3)->toDateString(),
        2,
    ))->toThrow(BookingNotAllowedException::class);
});

it('refuse un séjour plus court que le minimum', function () {
    expect(fn () => $this->service->hold(
        $this->property, $this->customer,
        $this->from,
        Carbon::parse($this->from)->addDay()->toDateString(),
        2,
    ))->toThrow(BookingNotAllowedException::class);
});

it('refuse plus de voyageurs que la capacité', function () {
    expect(fn () => $this->service->hold($this->property, $this->customer, $this->from, $this->to, 12))
        ->toThrow(BookingNotAllowedException::class);
});

it('refuse une villa non publiée', function () {
    $draft = Property::factory()->create();

    expect(fn () => $this->service->hold($draft, $this->customer, $this->from, $this->to, 2))
        ->toThrow(BookingNotAllowedException::class);
});

/*
|--------------------------------------------------------------------------
| Confirmation
|--------------------------------------------------------------------------
*/

it('confirme et fige la commission', function () {
    $booking = $this->service->hold($this->property, $this->customer, $this->from, $this->to, 4);

    $confirmed = $this->service->confirm($booking);

    // Le propriétaire ne gagne que sur les nuits : les frais de ménage et de
    // service reviennent entièrement à la plateforme, en plus de sa
    // commission sur les nuits.
    $nightly = $confirmed->nightly_subtotal->amount;
    $payout = $nightly - (int) round($nightly * 0.10);
    $commission = $confirmed->total_amount->amount - $payout;

    expect($confirmed->status)->toBe(BookingStatus::Confirmed)
        ->and($confirmed->hold_expires_at)->toBeNull()
        ->and($confirmed->owner_payout_amount->amount)->toBe($payout)
        ->and($confirmed->commission_amount->amount)->toBe($commission)
        ->and($confirmed->commission->status)->toBe(CommissionStatus::Pending);
});

it('ne réécrit pas une commission déjà figée quand le taux change', function () {
    $booking = $this->service->hold($this->property, $this->customer, $this->from, $this->to, 4);
    $confirmed = $this->service->confirm($booking);
    $figee = $confirmed->commission_amount->amount;

    Setting::put('platform.commission_rate', 25.0, 'commission');
    Setting::flushCache();

    expect($confirmed->fresh()->commission_amount->amount)->toBe($figee)
        ->and((float) $confirmed->fresh()->commission_rate)->toBe(10.0);
});

it('refuse une transition interdite', function () {
    $booking = $this->service->hold($this->property, $this->customer, $this->from, $this->to, 2);
    $this->service->confirm($booking);

    // Confirmée → Confirmée n'existe pas.
    expect(fn () => $this->service->confirm($booking->fresh()))
        ->toThrow(BookingNotAllowedException::class);
});

/*
|--------------------------------------------------------------------------
| Annulation et libération des dates
|--------------------------------------------------------------------------
*/

it('libère les dates à l\'annulation', function () {
    $booking = $this->service->hold($this->property, $this->customer, $this->from, $this->to, 2);

    $this->service->cancel($booking, reason: 'Changement de programme');

    expect(AvailabilityBlock::count())->toBe(0);

    // Les dates redeviennent réservables sur-le-champ.
    $second = $this->service->hold($this->property, User::factory()->create(), $this->from, $this->to, 2);

    expect($second->status)->toBe(BookingStatus::Pending);
});

it('annule la commission avec la réservation', function () {
    $booking = $this->service->hold($this->property, $this->customer, $this->from, $this->to, 2);
    $this->service->confirm($booking);
    $this->service->cancel($booking->fresh(), reason: 'Annulation client');

    expect($booking->fresh()->commission->status)->toBe(CommissionStatus::Cancelled);
});

/*
|--------------------------------------------------------------------------
| Expiration des tenues
|--------------------------------------------------------------------------
*/

it('libère les tenues de dates non payées et échues', function () {
    $stale = $this->service->hold($this->property, $this->customer, $this->from, $this->to, 2);
    $stale->forceFill(['hold_expires_at' => now()->subMinute()])->saveQuietly();

    $fresh = $this->service->hold(
        Property::factory()->published()->create(['min_nights' => 1]),
        $this->customer, $this->from, $this->to, 2,
    );

    $released = $this->service->expireStaleHolds();

    expect($released)->toBe(1)
        ->and($stale->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($fresh->fresh()->status)->toBe(BookingStatus::Pending)
        // Les dates de la réservation périmée sont de nouveau libres.
        ->and(AvailabilityBlock::where('property_id', $this->property->id)->count())->toBe(0);
});

it('ne périme jamais une réservation confirmée', function () {
    $booking = $this->service->hold($this->property, $this->customer, $this->from, $this->to, 2);
    $this->service->confirm($booking);
    $booking->fresh()->forceFill(['hold_expires_at' => now()->subHour()])->saveQuietly();

    expect($this->service->expireStaleHolds())->toBe(0)
        ->and($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});

it('clôt les séjours terminés, ce qui ouvre le droit à l\'avis', function () {
    $booking = $this->service->hold(
        $this->property, $this->customer,
        Carbon::today()->toDateString(),
        Carbon::today()->addDays(3)->toDateString(),
        2,
    );
    $this->service->confirm($booking);

    // On repousse la date de départ dans le passé sans toucher au blocage.
    $booking->fresh()->forceFill([
        'checkin_date' => Carbon::today()->subDays(5)->toDateString(),
        'checkout_date' => Carbon::today()->subDays(2)->toDateString(),
        'nights' => 3,
    ])->saveQuietly();

    expect($this->service->completeFinishedStays())->toBe(1)
        ->and($booking->fresh()->status)->toBe(BookingStatus::Completed)
        ->and($booking->fresh()->acceptsReview())->toBeTrue();
});
