<?php

declare(strict_types=1);

use App\Enums\BlockReason;
use App\Models\AvailabilityBlock;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| Calendrier public
|--------------------------------------------------------------------------
|
| Ce composant a porté un bug : il marquait le jour du départ comme pris.
| Ces tests verrouillent la sémantique de l'intervalle semi-ouvert, la même
| que celle de la contrainte PostgreSQL et du calcul de prix.
|
*/

beforeEach(function () {
    $this->property = Property::factory()->published()->create();
    PropertyImage::factory()->primary()->create(['property_id' => $this->property->id]);
});

/** Extrait les jours affichés comme indisponibles pour un mois donné. */
function blockedDaysIn(string $html, Carbon $month): array
{
    // Les jours pris portent la classe `line-through`.
    preg_match_all('/line-through[^>]*>\s*(\d+)\s*</u', $html, $matches);

    return array_map('intval', $matches[1] ?? []);
}

it('ne bloque pas le jour du départ', function () {
    $start = Carbon::today()->addMonth()->startOfMonth()->addDays(9);   // le 10
    $end = $start->copy()->addDays(4);                                  // le 14

    AvailabilityBlock::create([
        'property_id' => $this->property->id,
        'starts_on' => $start->toDateString(),
        'ends_on' => $end->toDateString(),
        'reason' => BlockReason::Booking,
    ]);

    $html = $this->get(route('villas.show', [$this->property->destination, $this->property]))->assertOk()->content();
    $blocked = blockedDaysIn($html, $start);

    // Du 10 au 13 inclus sont pris ; le 14, jour du départ, reste libre.
    expect($blocked)->toContain(10, 11, 12, 13)
        ->and($blocked)->not->toContain(14)
        ->and($blocked)->not->toContain(9);
});

it('bloque toutes les nuits d\'un séjour d\'une seule nuit', function () {
    $start = Carbon::today()->addMonth()->startOfMonth()->addDays(19);  // le 20

    AvailabilityBlock::create([
        'property_id' => $this->property->id,
        'starts_on' => $start->toDateString(),
        'ends_on' => $start->copy()->addDay()->toDateString(),
        'reason' => BlockReason::Manual,
    ]);

    $blocked = blockedDaysIn($this->get(route('villas.show', [$this->property->destination, $this->property]))->content(), $start);

    expect($blocked)->toContain(20)->and($blocked)->not->toContain(21);
});

it('n\'affiche aucun jour pris quand le calendrier est libre', function () {
    $blocked = blockedDaysIn($this->get(route('villas.show', [$this->property->destination, $this->property]))->content(), Carbon::today());

    expect($blocked)->toBeEmpty();
});

it('donne au calendrier la même définition de « libre » que la réservation', function () {
    $start = Carbon::today()->addMonth()->startOfMonth()->addDays(9);
    $end = $start->copy()->addDays(4);

    AvailabilityBlock::create([
        'property_id' => $this->property->id,
        'starts_on' => $start->toDateString(),
        'ends_on' => $end->toDateString(),
        'reason' => BlockReason::Booking,
    ]);

    // Le calendrier annonce le jour du départ libre : la base doit l'accepter.
    $state = sqlStateOf(fn () => AvailabilityBlock::create([
        'property_id' => $this->property->id,
        'starts_on' => $end->toDateString(),
        'ends_on' => $end->copy()->addDays(3)->toDateString(),
        'reason' => BlockReason::Booking,
    ]));

    expect($state)->toBeNull();
});
