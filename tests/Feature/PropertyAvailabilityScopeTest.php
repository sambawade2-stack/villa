<?php

declare(strict_types=1);

use App\Enums\BlockReason;
use App\Models\AvailabilityBlock;
use App\Models\Property;

/*
|--------------------------------------------------------------------------
| Cohérence entre l'affichage et la réservation
|--------------------------------------------------------------------------
|
| Le scope `availableBetween` s'appuie sur le même opérateur `&&` que la
| contrainte d'exclusion. Ces tests vérifient qu'une villa proposée à la
| recherche est bien une villa que la base acceptera de réserver — sans quoi
| le client verrait une disponibilité que le paiement lui refuserait.
|
*/

beforeEach(function () {
    $this->property = Property::factory()->published()->create();

    AvailabilityBlock::create([
        'property_id' => $this->property->id,
        'starts_on' => '2026-10-10',
        'ends_on' => '2026-10-15',
        'reason' => BlockReason::Booking,
    ]);
});

it('exclut une villa dont les dates sont prises', function (string $from, string $to) {
    expect(Property::query()->availableBetween($from, $to)->count())->toBe(0);
})->with([
    'chevauchement au début' => ['2026-10-08', '2026-10-12'],
    'chevauchement à la fin' => ['2026-10-13', '2026-10-18'],
    'période incluse' => ['2026-10-11', '2026-10-13'],
    'période englobante' => ['2026-10-01', '2026-10-30'],
    'dates identiques' => ['2026-10-10', '2026-10-15'],
]);

it('conserve une villa dont les dates sont libres', function (string $from, string $to) {
    expect(Property::query()->availableBetween($from, $to)->count())->toBe(1);
})->with([
    'avant, départ le jour de l arrivée bloquée' => ['2026-10-06', '2026-10-10'],
    'après, arrivée le jour du départ bloqué' => ['2026-10-15', '2026-10-20'],
    'bien avant' => ['2026-09-01', '2026-09-05'],
    'bien après' => ['2026-11-01', '2026-11-05'],
]);

it('ne filtre rien si les dates ne sont pas fournies', function () {
    expect(Property::query()->availableBetween(null, null)->count())->toBe(1);
});

it("s'accorde avec la contrainte : ce que le scope propose, la base l'accepte", function () {
    // Le scope annonce la villa libre du 15 au 20.
    expect(Property::query()->availableBetween('2026-10-15', '2026-10-20')->count())->toBe(1);

    // La base doit donc accepter le blocage correspondant.
    $state = sqlStateOf(fn () => AvailabilityBlock::create([
        'property_id' => $this->property->id,
        'starts_on' => '2026-10-15',
        'ends_on' => '2026-10-20',
        'reason' => BlockReason::Booking,
    ]));

    expect($state)->toBeNull();
});
