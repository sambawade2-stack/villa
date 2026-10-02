<?php

declare(strict_types=1);

use App\Enums\ComplianceItem;
use App\Enums\PropertyStatus;
use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyOwner;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->owner = PropertyOwner::factory()->create();
    $this->destination = Destination::factory()->create(['slug' => 'saly']);
});

/** Données minimales de création. */
function villaPayload(array $overrides = []): array
{
    return [
        'name' => 'Villa Nouvelle',
        'property_owner_id' => test()->owner->id,
        'destination_id' => test()->destination->id,
        'type' => 'villa',
        'capacity' => 6,
        'bedrooms' => 3,
        'bathrooms' => 2,
        ...$overrides,
    ];
}

/*
|--------------------------------------------------------------------------
| Création
|--------------------------------------------------------------------------
*/

it('crée une villa en brouillon et ouvre son édition', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.villas.store'), villaPayload())
        ->assertRedirect();

    $property = Property::firstOrFail();

    expect($property->status)->toBe(PropertyStatus::Draft)
        ->and($property->slug)->toBe('villa-nouvelle')
        ->and($property->published_at)->toBeNull()
        // Le dossier de conformité naît avec la villa.
        ->and($property->complianceChecks()->count())->toBe(count(ComplianceItem::ordered()));
});

it('rend l\'identifiant d\'URL unique', function () {
    Property::factory()->create(['slug' => 'villa-nouvelle', 'destination_id' => $this->destination->id]);

    $this->actingAs($this->admin)->post(route('admin.villas.store'), villaPayload());

    expect(Property::where('name', 'Villa Nouvelle')->latest('id')->first()->slug)->toBe('villa-nouvelle-2');
});

it('refuse la création à un client', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.villas.store'), villaPayload())
        ->assertNotFound();

    expect(Property::count())->toBe(0);
});

it('refuse un propriétaire ou une destination inexistants', function (array $bad) {
    $this->actingAs($this->admin)
        ->post(route('admin.villas.store'), villaPayload($bad))
        ->assertSessionHasErrors();

    expect(Property::count())->toBe(0);
})->with([
    'propriétaire inconnu' => [['property_owner_id' => 999]],
    'destination inconnue' => [['destination_id' => 999]],
]);

/*
|--------------------------------------------------------------------------
| Modification
|--------------------------------------------------------------------------
*/

it('enregistre la fiche complète', function () {
    $property = Property::factory()->create(['destination_id' => $this->destination->id]);
    $pool = Amenity::factory()->count(3)->create();

    $this->actingAs($this->admin)->put(route('admin.villas.update', $property), [
        'name' => 'Villa Teranga',
        'slug' => 'villa-teranga',
        'type' => 'villa',
        'property_owner_id' => $property->property_owner_id,
        'destination_id' => $this->destination->id,
        'description_fr' => 'Une villa lumineuse à cinq minutes de la plage.',
        'description_en' => 'A bright villa five minutes from the beach.',
        'capacity' => 8, 'bedrooms' => 4, 'beds' => 5, 'bathrooms' => 3,
        'base_price' => 250000, 'cleaning_fee' => 25000, 'security_deposit' => 100000,
        'min_nights' => 2, 'checkin_time' => '15:00', 'checkout_time' => '11:00',
        'pets_allowed' => 1,
        'amenities' => $pool->pluck('id')->all(),
    ])->assertRedirect();

    $property->refresh();

    expect($property->name)->toBe('Villa Teranga')
        ->and($property->description->get('fr'))->toBe('Une villa lumineuse à cinq minutes de la plage.')
        ->and($property->description->get('en'))->toBe('A bright villa five minutes from the beach.')
        ->and($property->base_price->amount)->toBe(250000)
        ->and($property->pets_allowed)->toBeTrue()
        ->and($property->parties_allowed)->toBeFalse()
        ->and($property->amenities)->toHaveCount(3);
});

it('refuse un identifiant d\'URL déjà pris', function () {
    Property::factory()->create(['slug' => 'deja-pris']);
    $property = Property::factory()->create(['destination_id' => $this->destination->id]);

    $this->actingAs($this->admin)->put(route('admin.villas.update', $property), [
        'name' => 'X', 'slug' => 'deja-pris', 'type' => 'villa',
        'property_owner_id' => $property->property_owner_id, 'destination_id' => $this->destination->id,
        'capacity' => 2, 'bedrooms' => 1, 'beds' => 1, 'bathrooms' => 1,
        'base_price' => 1000, 'cleaning_fee' => 0, 'security_deposit' => 0,
        'min_nights' => 1, 'checkin_time' => '15:00', 'checkout_time' => '11:00',
    ])->assertSessionHasErrors('slug');
});

/*
|--------------------------------------------------------------------------
| Publication
|--------------------------------------------------------------------------
*/

it('refuse de publier une fiche incomplète', function () {
    $property = Property::factory()->create([
        'destination_id' => $this->destination->id,
        'description' => [], 'base_price' => 0,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.villas.publish', $property))
        ->assertSessionHas('error');

    expect($property->fresh()->status)->toBe(PropertyStatus::Draft);
});

it('publie une fiche complète et floute les coordonnées', function () {
    $property = Property::factory()->create([
        'destination_id' => $this->destination->id,
        'description' => ['fr' => 'Une belle villa.'],
        'base_price' => 200000,
        'latitude' => 14.4419000, 'longitude' => -17.0086000,
    ]);
    PropertyImage::factory()->primary()->create(['property_id' => $property->id]);

    $this->actingAs($this->admin)->post(route('admin.villas.publish', $property))->assertRedirect();

    $property->refresh();

    expect($property->status)->toBe(PropertyStatus::Published)
        ->and($property->published_at)->not->toBeNull()
        ->and($property->approx_latitude)->not->toBeNull()
        // Floutée, mais pas au point de sortir du quartier.
        ->and(abs((float) $property->approx_latitude - 14.4419))->toBeLessThan(0.005)
        ->and((float) $property->approx_latitude)->not->toBe(14.4419);
});

it('donne toujours la même position floutée pour une villa', function () {
    $property = Property::factory()->create([
        'destination_id' => $this->destination->id,
        'description' => ['fr' => 'Une belle villa.'], 'base_price' => 200000,
        'latitude' => 14.44, 'longitude' => -17.00,
    ]);
    PropertyImage::factory()->primary()->create(['property_id' => $property->id]);

    $this->actingAs($this->admin)->post(route('admin.villas.publish', $property));
    $first = $property->fresh()->approx_latitude;

    // 'status' n'est plus mass-assignable : ce test remet la villa en
    // brouillon directement sur le modèle, pour republier ensuite.
    $property->forceFill(['status' => PropertyStatus::Draft])->save();
    $this->actingAs($this->admin)->post(route('admin.villas.publish', $property));

    // Sinon le point sauterait sur la carte à chaque republication.
    expect($property->fresh()->approx_latitude)->toBe($first);
});

it('dépublie sans supprimer', function () {
    $property = Property::factory()->published()->create(['destination_id' => $this->destination->id]);

    $this->actingAs($this->admin)
        ->post(route('admin.villas.unpublish', $property), ['status' => 'unpublished'])
        ->assertRedirect();

    expect($property->fresh()->status)->toBe(PropertyStatus::Unpublished);
});

it('refuse de supprimer une villa qui porte des réservations en cours', function () {
    $property = Property::factory()->published()->create(['destination_id' => $this->destination->id]);
    Booking::factory()->confirmed()->create(['property_id' => $property->id]);

    $this->actingAs($this->admin)
        ->delete(route('admin.villas.destroy', $property))
        ->assertSessionHas('error');

    expect($property->fresh())->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Les écrans se rendent réellement
|--------------------------------------------------------------------------
|
| Une méthode manquante sur un modèle ne se voit qu'au rendu. Ces tests
| ouvrent les écrans dans leurs deux états — vide et rempli — car c'est
| précisément l'écran garni de photos qui avait laissé passer une erreur.
|
*/

it('affiche le formulaire de création', function () {
    $this->actingAs($this->admin)->get(route('admin.villas.create'))
        ->assertOk()
        ->assertSee('Nouvelle villa')
        ->assertSee($this->owner->full_name);
});

it('affiche l\'écran d\'édition d\'une fiche vide', function () {
    $property = Property::factory()->create(['destination_id' => $this->destination->id]);

    $this->actingAs($this->admin)->get(route('admin.villas.edit', $property))
        ->assertOk()
        ->assertSee($property->name)
        ->assertSee('Aucune photo');
});

it('affiche l\'écran d\'édition avec ses photos et leurs tailles', function () {
    Storage::fake('properties');
    $property = Property::factory()->create(['destination_id' => $this->destination->id]);

    $this->actingAs($this->admin)->post(route('admin.villas.photos.store', $property), [
        'photos' => [
            UploadedFile::fake()->image('un.jpg', 1600, 1200),
            UploadedFile::fake()->image('deux.jpg', 1600, 1200),
        ],
    ]);

    $this->actingAs($this->admin)->get(route('admin.villas.edit', $property))
        ->assertOk()
        ->assertSee('Couverture');
});

it('affiche la liste des villas avec leurs indicateurs', function () {
    Property::factory()->published()->create(['destination_id' => $this->destination->id, 'name' => 'Villa Listée']);

    $this->actingAs($this->admin)->get(route('admin.villas.index'))
        ->assertOk()
        ->assertSee('Villa Listée')
        ->assertSee('Ajouter une villa');
});

/*
|--------------------------------------------------------------------------
| Photos
|--------------------------------------------------------------------------
*/

it('enregistre une photo et produit ses quatre tailles', function () {
    Storage::fake('properties');
    $property = Property::factory()->create(['destination_id' => $this->destination->id]);

    $this->actingAs($this->admin)->post(route('admin.villas.photos.store', $property), [
        'photos' => [UploadedFile::fake()->image('salon.jpg', 1600, 1200)],
    ])->assertRedirect();

    $image = $property->images()->firstOrFail();

    expect($image->is_primary)->toBeTrue()
        ->and($image->conversions)->toHaveKeys(['thumb', 'card', 'hero', 'full']);

    foreach (['thumb', 'card', 'hero', 'full'] as $size) {
        Storage::disk('properties')->assertExists($image->conversions[$size]);
        // Les dérivés sont en WebP : plus légers, largement supportés.
        expect($image->conversions[$size])->toEndWith('.webp');
    }
});

it('accepte une image de petite taille, sans largeur minimale imposée', function () {
    Storage::fake('properties');
    $property = Property::factory()->create(['destination_id' => $this->destination->id]);

    $this->actingAs($this->admin)->post(route('admin.villas.photos.store', $property), [
        'photos' => [UploadedFile::fake()->image('petite.jpg', 400, 300)],
    ])->assertSessionDoesntHaveErrors();

    expect($property->images()->count())->toBe(1);
});

it('refuse un fichier qui n\'est pas une image', function () {
    Storage::fake('properties');
    $property = Property::factory()->create(['destination_id' => $this->destination->id]);

    $this->actingAs($this->admin)->post(route('admin.villas.photos.store', $property), [
        'photos' => [UploadedFile::fake()->create('charge.php', 40, 'application/x-php')],
    ])->assertSessionHasErrors();

    expect($property->images()->count())->toBe(0);
});

it('ne garde qu\'une seule image de couverture', function () {
    Storage::fake('properties');
    $property = Property::factory()->create(['destination_id' => $this->destination->id]);

    $this->actingAs($this->admin)->post(route('admin.villas.photos.store', $property), [
        'photos' => [
            UploadedFile::fake()->image('un.jpg', 1600, 1200),
            UploadedFile::fake()->image('deux.jpg', 1600, 1200),
        ],
    ]);

    $second = $property->images()->orderByDesc('position')->first();

    $this->actingAs($this->admin)
        ->post(route('admin.villas.photos.primary', [$property, $second]))
        ->assertRedirect();

    expect($property->images()->where('is_primary', true)->count())->toBe(1)
        ->and($second->fresh()->is_primary)->toBeTrue();
});

it('reste sans effet si la photo est déjà la couverture', function () {
    Storage::fake('properties');
    $property = Property::factory()->create(['destination_id' => $this->destination->id]);

    $this->actingAs($this->admin)->post(route('admin.villas.photos.store', $property), [
        'photos' => [UploadedFile::fake()->image('un.jpg', 1600, 1200)],
    ]);

    $cover = $property->images()->firstOrFail();

    // Le cas qui laissait la villa sans couverture.
    $this->actingAs($this->admin)->post(route('admin.villas.photos.primary', [$property, $cover]));

    expect($property->images()->where('is_primary', true)->count())->toBe(1);
});

it('promeut la photo suivante quand la couverture est supprimée', function () {
    Storage::fake('properties');
    $property = Property::factory()->create(['destination_id' => $this->destination->id]);

    $this->actingAs($this->admin)->post(route('admin.villas.photos.store', $property), [
        'photos' => [
            UploadedFile::fake()->image('un.jpg', 1600, 1200),
            UploadedFile::fake()->image('deux.jpg', 1600, 1200),
        ],
    ]);

    $cover = $property->images()->where('is_primary', true)->firstOrFail();

    $this->actingAs($this->admin)->delete(route('admin.villas.photos.destroy', [$property, $cover]));

    // Mieux vaut une couverture arbitraire qu'une villa sans visuel.
    expect($property->images()->count())->toBe(1)
        ->and($property->images()->first()->is_primary)->toBeTrue();
});

it('interdit d\'agir sur la photo d\'une autre villa', function () {
    Storage::fake('properties');
    $a = Property::factory()->create(['destination_id' => $this->destination->id]);
    $b = Property::factory()->create(['destination_id' => $this->destination->id]);

    $this->actingAs($this->admin)->post(route('admin.villas.photos.store', $b), [
        'photos' => [UploadedFile::fake()->image('x.jpg', 1600, 1200)],
    ]);
    $image = $b->images()->firstOrFail();

    $this->actingAs($this->admin)
        ->delete(route('admin.villas.photos.destroy', [$a, $image]))
        ->assertNotFound();

    expect($b->images()->count())->toBe(1);
});

it('refuse de réordonner avec l\'identifiant d\'une photo appartenant à une autre villa', function () {
    Storage::fake('properties');
    $a = Property::factory()->create(['destination_id' => $this->destination->id]);
    $b = Property::factory()->create(['destination_id' => $this->destination->id]);

    $this->actingAs($this->admin)->post(route('admin.villas.photos.store', $a), [
        'photos' => [UploadedFile::fake()->image('a.jpg', 1600, 1200)],
    ]);
    $ownImage = $a->images()->firstOrFail();

    $this->actingAs($this->admin)->post(route('admin.villas.photos.store', $b), [
        'photos' => [UploadedFile::fake()->image('b.jpg', 1600, 1200)],
    ]);
    $foreignImage = $b->images()->firstOrFail();

    $this->actingAs($this->admin)
        ->post(route('admin.villas.photos.reorder', $a), [
            'order' => [$ownImage->id, $foreignImage->id],
        ])
        ->assertNotFound();

    expect($ownImage->fresh()->position)->toBe($ownImage->position);
});
