<?php

declare(strict_types=1);

use App\Enums\BlockReason;
use App\Enums\PropertyStatus;
use App\Models\Amenity;
use App\Models\AvailabilityBlock;
use App\Models\Destination;
use App\Models\PricingRule;
use App\Models\Property;
use App\Models\PropertyImage;

beforeEach(function () {
    $this->destination = Destination::factory()->create(['slug' => 'saly', 'name' => ['fr' => 'Saly', 'en' => 'Saly']]);
});

/*
|--------------------------------------------------------------------------
| Accueil
|--------------------------------------------------------------------------
*/

it('affiche la page d\'accueil', function () {
    $property = Property::factory()->published()->create(['destination_id' => $this->destination->id]);
    PropertyImage::factory()->primary()->create(['property_id' => $property->id]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Trouvez la villa idéale sur la Petite Côte')
        ->assertSee($property->name)
        ->assertSee('Saly');
});

it('ne montre pas les villas non publiées sur l\'accueil', function () {
    $draft = Property::factory()->create(['status' => PropertyStatus::Draft, 'name' => 'Villa Brouillon']);

    $this->get(route('home'))->assertOk()->assertDontSee('Villa Brouillon');
});

/*
|--------------------------------------------------------------------------
| Fiche villa
|--------------------------------------------------------------------------
*/

it('affiche une villa publiée', function () {
    $property = Property::factory()->published()->create([
        'destination_id' => $this->destination->id,
        'description' => ['fr' => 'Une villa avec vue sur la lagune.'],
    ]);
    PropertyImage::factory()->primary()->create(['property_id' => $property->id]);

    $this->get(route('villas.show', [$property->destination, $property]))
        ->assertOk()
        ->assertSee($property->name)
        ->assertSee('Une villa avec vue sur la lagune.')
        ->assertSee('Disponibilités');
});

it('affiche en tête le tarif réellement appliqué aux dates, pas le tarif de base brut', function () {
    // Un tarif de saison à 360 000, alors que le tarif de base est à 450 000 :
    // le prix en tête doit correspondre à ce que la ligne de détail facture
    // réellement, sans quoi les deux chiffres ne se recoupent jamais.
    $property = Property::factory()->published()->create([
        'destination_id' => $this->destination->id,
        'base_price' => 450_000,
        'min_nights' => 1,
    ]);
    PropertyImage::factory()->primary()->create(['property_id' => $property->id]);
    PricingRule::factory()->create([
        'property_id' => $property->id,
        'starts_on' => '2026-06-01',
        'ends_on' => '2026-09-30',
        'price_per_night' => 360_000,
        'priority' => 5,
    ]);

    $response = $this->get(route('villas.show', [
        $property->destination, $property,
    ]).'?checkin=2026-08-21&checkout=2026-08-28&guests=2');

    $response->assertOk()
        ->assertSee("360\u{202F}000")
        ->assertSee('Tarif moyen pour ces dates');

    // Le tarif de base brut ne doit apparaître qu'en mention secondaire,
    // jamais comme le prix par nuit mis en avant.
    expect(substr_count($response->content(), "450\u{202F}000"))->toBe(1);
});

it('affiche le tarif de base quand il correspond déjà au tarif appliqué', function () {
    $property = Property::factory()->published()->create([
        'destination_id' => $this->destination->id,
        'base_price' => 250_000,
        'weekend_price' => null,
        'min_nights' => 1,
    ]);
    PropertyImage::factory()->primary()->create(['property_id' => $property->id]);

    $response = $this->get(route('villas.show', [
        $property->destination, $property,
    ]).'?checkin=2026-08-21&checkout=2026-08-24&guests=2');

    $response->assertOk()
        ->assertSee("250\u{202F}000")
        ->assertDontSee('Tarif moyen pour ces dates');
});

it('renvoie 404 pour une villa non publiée', function (PropertyStatus $status) {
    $property = Property::factory()->create(['status' => $status]);

    $this->get(route('villas.show', [$property->destination, $property]))->assertNotFound();
})->with([
    'brouillon' => PropertyStatus::Draft,
    'dépubliée' => PropertyStatus::Unpublished,
    'suspendue' => PropertyStatus::Suspended,
]);

it('échappe le contenu de la villa dans le bloc JSON-LD, sans possibilité d\'en sortir', function () {
    // Un nom ou une description contenant littéralement "</script>" ne doit
    // jamais pouvoir refermer la balise et injecter du HTML à sa suite — c'est
    // aujourd'hui un champ réservé à l'administrateur, mais la v2 documentée
    // (docs/architecture.html) l'ouvre aux propriétaires : autant que ce bloc
    // reste sûr indépendamment de qui écrit le contenu.
    $payload = '</script><script>alert(document.cookie)</script>';

    $property = Property::factory()->published()->create([
        'destination_id' => $this->destination->id,
        'name' => 'Villa '.$payload,
        'short_description' => ['fr' => $payload],
    ]);
    PropertyImage::factory()->primary()->create(['property_id' => $property->id]);

    $html = $this->get(route('villas.show', [$property->destination, $property]))
        ->assertOk()
        ->content();

    // La charge utile ne doit apparaître nulle part en clair : ni la balise
    // fermante, ni le script injecté.
    expect($html)->not->toContain('</script><script>alert(document.cookie)</script>')
        ->and($html)->not->toContain('<script>alert(document.cookie)</script>');

    // Le bloc JSON-LD doit rester un JSON valide et unique par page : s'il
    // s'était refermé prématurément, il y aurait deux balises <script
    // type="application/ld+json"> au lieu d'une, ou un JSON invalide.
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);
    expect($matches)->toHaveCount(2);
    $decoded = json_decode(trim($matches[1]), associative: true);
    expect(json_last_error())->toBe(JSON_ERROR_NONE)
        ->and($decoded['name'])->toContain('</script>');
});

it('n\'expose jamais l\'adresse interne ni les coordonnées exactes', function () {
    $property = Property::factory()->published()->create([
        'destination_id' => $this->destination->id,
        'internal_address' => 'Lot 42 cité Malick Sy',
        'internal_notes' => 'Code portail 4477',
        'latitude' => 14.4419123,
        'longitude' => -17.0086456,
    ]);
    PropertyImage::factory()->primary()->create(['property_id' => $property->id]);

    $this->get(route('villas.show', [$property->destination, $property]))
        ->assertOk()
        ->assertDontSee('Lot 42 cité Malick Sy')
        ->assertDontSee('Code portail 4477')
        ->assertDontSee('14.4419123')
        ->assertDontSee('-17.0086456');
});

it('ne publie que le prénom de l\'hôte', function () {
    $property = Property::factory()->published()->create(['destination_id' => $this->destination->id]);
    $property->owner->update(['first_name' => 'Mamadou', 'last_name' => 'Ndiayefall', 'phone' => '+221770001122']);
    PropertyImage::factory()->primary()->create(['property_id' => $property->id]);

    $this->get(route('villas.show', [$property->destination, $property]))
        ->assertOk()
        ->assertSee('Mamadou')
        ->assertDontSee('Ndiayefall')
        ->assertDontSee('+221770001122');
});

/*
|--------------------------------------------------------------------------
| Recherche
|--------------------------------------------------------------------------
*/

it('liste les villas publiées', function () {
    Property::factory()->published()->count(3)->create(['destination_id' => $this->destination->id]);
    Property::factory()->create(['status' => PropertyStatus::Draft]);

    $this->get(route('villas.index'))
        ->assertOk()
        ->assertSee('3 villas disponibles');
});

it('filtre par destination', function () {
    $other = Destination::factory()->create(['slug' => 'somone']);
    Property::factory()->published()->create(['destination_id' => $this->destination->id, 'name' => 'Villa Saly']);
    Property::factory()->published()->create(['destination_id' => $other->id, 'name' => 'Villa Somone']);

    $this->get(route('villas.index', ['destination' => 'saly']))
        ->assertOk()
        ->assertSee('Villa Saly')
        ->assertDontSee('Villa Somone');
});

it('filtre par capacité', function () {
    Property::factory()->published()->create(['capacity' => 4, 'name' => 'Villa Petite']);
    Property::factory()->published()->create(['capacity' => 12, 'name' => 'Villa Grande']);

    $this->get(route('villas.index', ['guests' => 10]))
        ->assertOk()
        ->assertSee('Villa Grande')
        ->assertDontSee('Villa Petite');
});

it('filtre par budget', function () {
    Property::factory()->published()->create(['base_price' => 100_000, 'name' => 'Villa Abordable']);
    Property::factory()->published()->create(['base_price' => 600_000, 'name' => 'Villa Prestige']);

    $this->get(route('villas.index', ['price_max' => 200_000]))
        ->assertOk()
        ->assertSee('Villa Abordable')
        ->assertDontSee('Villa Prestige');
});

it('exige tous les équipements cochés', function () {
    $pool = Amenity::factory()->create(['slug' => 'piscine', 'is_filterable' => true]);
    $wifi = Amenity::factory()->create(['slug' => 'wifi', 'is_filterable' => true]);

    $both = Property::factory()->published()->create(['name' => 'Villa Complete']);
    $one = Property::factory()->published()->create(['name' => 'Villa Partielle']);

    $both->amenities()->sync([$pool->id, $wifi->id]);
    $one->amenities()->sync([$pool->id]);

    $this->get(route('villas.index', ['amenities' => ['piscine', 'wifi']]))
        ->assertOk()
        ->assertSee('Villa Complete')
        ->assertDontSee('Villa Partielle');
});

it('écarte une villa dont les dates demandées sont prises', function () {
    $free = Property::factory()->published()->create(['name' => 'Villa Libre']);
    $taken = Property::factory()->published()->create(['name' => 'Villa Occupee']);

    AvailabilityBlock::create([
        'property_id' => $taken->id,
        'starts_on' => now()->addMonth()->toDateString(),
        'ends_on' => now()->addMonth()->addDays(7)->toDateString(),
        'reason' => BlockReason::Booking,
    ]);

    $this->get(route('villas.index', [
        'checkin' => now()->addMonth()->addDays(2)->toDateString(),
        'checkout' => now()->addMonth()->addDays(5)->toDateString(),
    ]))
        ->assertOk()
        ->assertSee('Villa Libre')
        ->assertDontSee('Villa Occupee');
});

it('trie par prix', function () {
    Property::factory()->published()->create(['base_price' => 500_000, 'name' => 'Villa Chere']);
    Property::factory()->published()->create(['base_price' => 100_000, 'name' => 'Villa Economique']);

    $response = $this->get(route('villas.index', ['sort' => 'prix-croissant']))->assertOk();

    expect(strpos($response->content(), 'Villa Economique'))
        ->toBeLessThan(strpos($response->content(), 'Villa Chere'));
});

it('refuse une date de départ antérieure à l\'arrivée', function () {
    $this->get(route('villas.index', [
        'checkin' => now()->addMonth()->toDateString(),
        'checkout' => now()->addWeek()->toDateString(),
    ]))->assertSessionHasErrors('checkout');
});

it('ignore une arrivée sans départ plutôt que de refuser la page', function () {
    Property::factory()->published()->create();

    $this->get(route('villas.index', ['checkin' => now()->addMonth()->toDateString()]))
        ->assertOk()
        ->assertSessionHasNoErrors();
});

/*
|--------------------------------------------------------------------------
| Destinations
|--------------------------------------------------------------------------
*/

it('affiche une destination et ses villas', function () {
    Property::factory()->published()->create(['destination_id' => $this->destination->id, 'name' => 'Villa Teranga']);

    $this->get(route('destinations.show', $this->destination))
        ->assertOk()
        ->assertSee('Saly')
        ->assertSee('Villa Teranga');
});

it('renvoie 404 pour une destination désactivée', function () {
    $this->destination->update(['is_active' => false]);

    $this->get(route('destinations.show', $this->destination))->assertNotFound();
});
