<?php

declare(strict_types=1);

use App\Enums\BlockReason;
use App\Models\AvailabilityBlock;
use App\Models\Property;

/*
|--------------------------------------------------------------------------
| Garantie anti-double-réservation
|--------------------------------------------------------------------------
|
| Ces tests portent sur la contrainte PostgreSQL `availability_blocks_no_overlap`.
| Ils ne vérifient pas du code PHP : ils vérifient que le moteur refuse lui-même
| les chevauchements. C'est la seule protection qui tienne face à deux requêtes
| simultanées.
|
*/

beforeEach(function () {
    $this->property = Property::factory()->create();

    $this->block = fn (string $from, string $to, ?Property $property = null) => AvailabilityBlock::create([
        'property_id' => ($property ?? $this->property)->id,
        'starts_on' => $from,
        'ends_on' => $to,
        'reason' => BlockReason::Manual,
    ]);
});

it('accepte un premier blocage', function () {
    ($this->block)('2026-09-10', '2026-09-14');

    expect(AvailabilityBlock::count())->toBe(1);
});

it('refuse un chevauchement partiel', function () {
    ($this->block)('2026-09-10', '2026-09-14');

    $state = sqlStateOf(fn () => ($this->block)('2026-09-13', '2026-09-16'));

    expect($state)->toBe(EXCLUSION_VIOLATION)
        ->and(AvailabilityBlock::count())->toBe(1);
});

it('refuse un englobement total', function () {
    ($this->block)('2026-09-10', '2026-09-20');

    expect(sqlStateOf(fn () => ($this->block)('2026-09-12', '2026-09-15')))
        ->toBe(EXCLUSION_VIOLATION);
});

it('refuse des dates strictement identiques', function () {
    ($this->block)('2026-09-10', '2026-09-14');

    expect(sqlStateOf(fn () => ($this->block)('2026-09-10', '2026-09-14')))
        ->toBe(EXCLUSION_VIOLATION);
});

it('autorise une arrivée le jour du départ précédent', function () {
    // La borne haute est exclue : le partant libère la villa le matin,
    // l'arrivant s'installe l'après-midi. Deux séjours, aucun conflit.
    ($this->block)('2026-09-10', '2026-09-14');
    ($this->block)('2026-09-14', '2026-09-18');

    expect(AvailabilityBlock::count())->toBe(2);
});

it('autorise les mêmes dates sur une autre villa', function () {
    $other = Property::factory()->create();

    ($this->block)('2026-09-10', '2026-09-14');
    ($this->block)('2026-09-10', '2026-09-14', $other);

    expect(AvailabilityBlock::count())->toBe(2);
});

it('refuse une période qui se termine avant de commencer', function () {
    // Rejetée par daterange() au calcul de la colonne générée, donc en 22000
    // et non par le CHECK — qui n'a jamais l'occasion de s'exécuter.
    expect(sqlStateOf(fn () => ($this->block)('2026-09-14', '2026-09-10')))
        ->toBe(DATA_EXCEPTION);
});

it('refuse une période de durée nulle', function () {
    // Ici daterange() produit un intervalle vide, parfaitement valide :
    // c'est bien le CHECK `ends_on > starts_on` qui refuse.
    expect(sqlStateOf(fn () => ($this->block)('2026-09-14', '2026-09-14')))
        ->toBe(CHECK_VIOLATION);
});

it('libère les dates quand le blocage est supprimé', function () {
    $first = ($this->block)('2026-09-10', '2026-09-14');

    expect(sqlStateOf(fn () => ($this->block)('2026-09-12', '2026-09-16')))
        ->toBe(EXCLUSION_VIOLATION);

    $first->delete();

    ($this->block)('2026-09-12', '2026-09-16');

    expect(AvailabilityBlock::count())->toBe(1);
});
