<?php

declare(strict_types=1);

use App\Enums\PropertyStatus;
use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Support\Money;

it('exige les informations minimales avant publication', function () {
    $property = Property::factory()->create([
        'description' => [],
        'base_price' => 0,
    ]);

    expect($property->publicationBlockers())
        ->toContain('description', 'images', 'base_price')
        ->and($property->isPublishable())->toBeFalse();
});

it('devient publiable une fois les manques comblés', function () {
    $property = Property::factory()->create([
        'description' => [],
        'base_price' => 0,
    ]);

    $property->update([
        'description' => ['fr' => 'Une villa lumineuse à cinq minutes de la plage.'],
        'base_price' => 250_000,
    ]);
    PropertyImage::factory()->primary()->create(['property_id' => $property->id]);

    expect($property->fresh()->publicationBlockers())->toBe([])
        ->and($property->fresh()->isPublishable())->toBeTrue();
});

it('convertit les prix en Money', function () {
    $property = Property::factory()->create(['base_price' => 250_000]);

    expect($property->base_price)->toBeInstanceOf(Money::class)
        ->and($property->base_price->amount)->toBe(250_000)
        ->and($property->base_price->format())->toBe("250\u{202F}000\u{202F}FCFA");
});

it('sert la description dans la locale courante avec repli', function () {
    $property = Property::factory()->create([
        'description' => ['fr' => 'Villa avec piscine', 'en' => 'Villa with pool'],
    ]);

    expect($property->description->get('fr'))->toBe('Villa avec piscine')
        ->and($property->description->get('en'))->toBe('Villa with pool');

    // Locale absente : repli sur la locale de secours, jamais une page vide.
    app()->setLocale('es');
    expect((string) $property->fresh()->description)->toBe('Villa avec piscine');
});

it("n'expose jamais la position exacte ni l'adresse interne en JSON", function () {
    $property = Property::factory()->published()->create([
        'internal_address' => 'Lot 42, cité privée, Saly',
        'internal_notes' => 'Code du portail : 4477',
    ]);

    $json = $property->toArray();

    expect($json)->not->toHaveKeys(['internal_address', 'internal_notes', 'latitude', 'longitude'])
        ->and($json)->toHaveKeys(['approx_latitude', 'approx_longitude']);
});

it('ne retient que les villas publiées dans le scope public', function () {
    Property::factory()->published()->count(3)->create();
    Property::factory()->count(2)->create(['status' => PropertyStatus::Draft]);
    Property::factory()->create(['status' => PropertyStatus::Suspended]);

    expect(Property::query()->published()->count())->toBe(3)
        ->and(Property::query()->count())->toBe(6);
});

it('filtre par capacité et par nombre de chambres', function () {
    Property::factory()->create(['capacity' => 4, 'bedrooms' => 2]);
    Property::factory()->create(['capacity' => 8, 'bedrooms' => 4]);
    Property::factory()->create(['capacity' => 12, 'bedrooms' => 6]);

    expect(Property::query()->forGuests(8)->count())->toBe(2)
        ->and(Property::query()->withBedroomsAtLeast(4)->count())->toBe(2)
        ->and(Property::query()->forGuests(12)->withBedroomsAtLeast(6)->count())->toBe(1);
});

it('exige tous les équipements demandés, pas seulement un', function () {
    $withBoth = Property::factory()->create();
    $withOne = Property::factory()->create();

    $pool = Amenity::factory()->create(['slug' => 'piscine']);
    $wifi = Amenity::factory()->create(['slug' => 'wifi']);

    $withBoth->amenities()->sync([$pool->id, $wifi->id]);
    $withOne->amenities()->sync([$pool->id]);

    expect(Property::query()->withAllAmenities(['piscine'])->count())->toBe(2)
        ->and(Property::query()->withAllAmenities(['piscine', 'wifi'])->count())->toBe(1);
});

it('refuse en base une capacité nulle', function () {
    expect(sqlStateOf(fn () => Property::factory()->create(['capacity' => 0])))
        ->toBe(CHECK_VIOLATION);
});

it('refuse en base un nombre de nuits incohérent avec les dates', function () {
    $property = Property::factory()->create();

    // La base recalcule : nights doit valoir checkout - checkin.
    expect(sqlStateOf(fn () => Booking::factory()->create([
        'property_id' => $property->id,
        'checkin_date' => '2026-10-01',
        'checkout_date' => '2026-10-08',
        'nights' => 3,
    ])))->toBe(CHECK_VIOLATION);
});
