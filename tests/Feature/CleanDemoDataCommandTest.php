<?php

declare(strict_types=1);

use App\Models\AvailabilityBlock;
use App\Models\Destination;
use App\Models\Favorite;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyOwner;
use App\Models\Setting;
use App\Models\User;
use App\Support\CatalogCache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
    $this->owner = PropertyOwner::factory()->create();
    $this->destination = Destination::factory()->create();
    $this->property = Property::factory()->published()->create([
        'destination_id' => $this->destination->id,
        'property_owner_id' => $this->owner->id,
    ]);
    PropertyImage::factory()->primary()->create(['property_id' => $this->property->id]);
    Favorite::create(['user_id' => $this->customer->id, 'property_id' => $this->property->id]);
});

it('supprime tout le contenu de démonstration', function () {
    $this->artisan('demo:clean', ['--force' => true])->assertSuccessful();

    expect(Property::withTrashed()->count())->toBe(0)
        ->and(PropertyOwner::withTrashed()->count())->toBe(0)
        ->and(User::customers()->count())->toBe(0)
        ->and(Favorite::count())->toBe(0)
        ->and(PropertyImage::count())->toBe(0);
});

it('conserve le compte administrateur, les destinations, les équipements et les réglages', function () {
    $settingsBefore = Setting::count();

    $this->artisan('demo:clean', ['--force' => true]);

    expect(User::admins()->count())->toBe(1)
        ->and(User::find($this->admin->id))->not->toBeNull()
        ->and(Destination::count())->toBeGreaterThan(0)
        ->and(Destination::find($this->destination->id))->not->toBeNull()
        ->and(Setting::count())->toBe($settingsBefore);
});

it('demande confirmation et n\'efface rien si elle est refusée', function () {
    $this->artisan('demo:clean')
        ->expectsConfirmation(
            'Supprimer définitivement ces données ? Le compte administrateur, les destinations, '
            .'les équipements et les réglages seront conservés.',
            'no',
        )
        ->assertSuccessful();

    expect(Property::count())->toBe(1);
});

it('supprime sans confirmation avec --force', function () {
    $this->artisan('demo:clean', ['--force' => true])->assertSuccessful();

    expect(Property::count())->toBe(0);
});

it('ne fait rien et le signale s\'il n\'y a déjà rien à nettoyer', function () {
    $this->artisan('demo:clean', ['--force' => true]);

    $this->artisan('demo:clean', ['--force' => true])
        ->expectsOutputToContain('Rien à nettoyer')
        ->assertSuccessful();
});

it('retire les blocages de calendrier et les rend disponibles au reseeding', function () {
    AvailabilityBlock::create([
        'property_id' => $this->property->id,
        'starts_on' => now()->addMonth()->toDateString(),
        'ends_on' => now()->addMonth()->addDays(3)->toDateString(),
        'reason' => 'manual',
    ]);

    $this->artisan('demo:clean', ['--force' => true]);

    expect(AvailabilityBlock::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Le bug réel : le cache du catalogue ne se vide pas tout seul
|--------------------------------------------------------------------------
|
| Property et Destination invalident CatalogCache depuis des événements de
| modèle (static::saved / static::deleted), qui ne se déclenchent que sur un
| $model->delete() individuel — jamais sur les suppressions en masse que
| cette commande utilise pour rester rapide sur des centaines de lignes.
| Sans un appel explicite à CatalogCache::flush(), la page d'accueil aurait
| continué d'afficher les anciens comptages jusqu'à l'expiration du cache,
| une heure plus tard.
|
*/

it('vide le cache du catalogue, sans quoi les comptages restent périmés jusqu\'à une heure', function () {
    // Peuple le cache AVANT le nettoyage, avec la villa encore publiée.
    $before = CatalogCache::destinationsWithCounts()
        ->firstWhere(fn ($summary) => $summary->destination->is($this->destination));

    expect($before->count)->toBe(1);

    $this->artisan('demo:clean', ['--force' => true]);

    $after = CatalogCache::destinationsWithCounts()
        ->firstWhere(fn ($summary) => $summary->destination->is($this->destination));

    expect($after->count)->toBe(0);
});

it('supprime les photos de démonstration du disque public', function () {
    Storage::disk('properties')->put('demo/exemple.jpg', 'contenu-image');

    $this->artisan('demo:clean', ['--force' => true]);

    Storage::disk('properties')->assertMissing('demo/exemple.jpg');
});
