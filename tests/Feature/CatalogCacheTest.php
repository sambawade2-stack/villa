<?php

declare(strict_types=1);

use App\Enums\PropertyStatus;
use App\Models\Destination;
use App\Models\Property;
use App\Support\CatalogCache;

/*
|--------------------------------------------------------------------------
| Cache du catalogue
|--------------------------------------------------------------------------
|
| Un cache n'a de valeur que si son invalidation est sûre. Ces tests portent
| moins sur le gain de temps que sur le fait que la donnée affichée reste vraie
| après une publication, une dépublication ou une suppression.
|
*/

/** Nombre de villas mis en cache pour une destination. */
function countFor(string $slug): int
{
    return CatalogCache::destinationsWithCounts()
        ->first(fn ($row) => $row->destination->slug === $slug)?->count ?? 0;
}

beforeEach(function () {
    $this->destination = Destination::factory()->create(['slug' => 'saly']);
    CatalogCache::flush();
});

it('sert les destinations depuis le cache', function () {
    CatalogCache::destinations();

    // La seconde lecture ne doit plus toucher la base.
    DB::enableQueryLog();
    DB::flushQueryLog();

    CatalogCache::destinations();

    expect(DB::getQueryLog())->toBeEmpty();
});

it('compte les villas publiées, et elles seules', function () {
    Property::factory()->published()->count(3)->create(['destination_id' => $this->destination->id]);
    Property::factory()->create(['destination_id' => $this->destination->id, 'status' => PropertyStatus::Draft]);

    expect(countFor('saly'))->toBe(3);
});

it('tombe dès qu\'une villa est publiée', function () {
    Property::factory()->published()->create(['destination_id' => $this->destination->id]);
    expect(countFor('saly'))->toBe(1);

    Property::factory()->published()->create(['destination_id' => $this->destination->id]);

    expect(countFor('saly'))->toBe(2);
});

it('tombe dès qu\'une villa est dépubliée', function () {
    $property = Property::factory()->published()->create(['destination_id' => $this->destination->id]);
    expect(countFor('saly'))->toBe(1);

    $property->update(['status' => PropertyStatus::Unpublished]);

    expect(countFor('saly'))->toBe(0);
});

it('tombe dès qu\'une villa est supprimée', function () {
    $property = Property::factory()->published()->create(['destination_id' => $this->destination->id]);
    CatalogCache::destinationsWithCounts();

    $property->delete();

    expect(countFor('saly'))->toBe(0);
});

it('tombe dès qu\'une destination est désactivée', function () {
    expect(CatalogCache::destinations()->pluck('slug'))->toContain('saly');

    $this->destination->update(['is_active' => false]);

    expect(CatalogCache::destinations()->pluck('slug'))->not->toContain('saly');
});

it('reflète une nouvelle destination sans attendre l\'expiration', function () {
    CatalogCache::destinations();

    Destination::factory()->create(['slug' => 'somone']);

    expect(CatalogCache::destinations()->pluck('slug'))->toContain('somone');
});

it('affiche un compte juste sur la page d\'accueil après publication', function () {
    Property::factory()->published()->count(2)->create(['destination_id' => $this->destination->id]);

    $this->get(route('home'))->assertOk()->assertSee('2 villas');

    Property::factory()->published()->create(['destination_id' => $this->destination->id]);

    $this->get(route('home'))->assertOk()->assertSee('3 villas');
});
