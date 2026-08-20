<?php

declare(strict_types=1);

use App\Enums\PropertyStatus;
use App\Models\Destination;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;

beforeEach(function () {
    $this->destination = Destination::factory()->create(['slug' => 'saly', 'name' => ['fr' => 'Saly', 'en' => 'Saly']]);
    $this->property = Property::factory()->published()->create([
        'destination_id' => $this->destination->id,
        'slug' => 'villa-teranga',
        'name' => 'Villa Teranga',
    ]);
    PropertyImage::factory()->primary()->create(['property_id' => $this->property->id]);
});

/*
|--------------------------------------------------------------------------
| Adresses
|--------------------------------------------------------------------------
*/

it('sert la fiche villa à son adresse canonique', function () {
    $this->get('/villas/saly/villa-teranga')->assertOk()->assertSee('Villa Teranga');
});

it('redirige l\'ancienne adresse en 301', function () {
    // Un contenu ne doit exister qu'à une adresse : les anciens liens et les
    // moteurs sont renvoyés définitivement vers la nouvelle.
    $this->get('/villas/villa-teranga')
        ->assertStatus(301)
        ->assertRedirect('/villas/saly/villa-teranga');
});

it('corrige une destination erronée dans l\'adresse', function () {
    Destination::factory()->create(['slug' => 'popenguine']);

    $this->get('/villas/popenguine/villa-teranga')
        ->assertStatus(301)
        ->assertRedirect('/villas/saly/villa-teranga');
});

it('renvoie 404 sur une villa non publiée, quelle que soit la forme de l\'adresse', function () {
    $draft = Property::factory()->create([
        'status' => PropertyStatus::Draft,
        'destination_id' => $this->destination->id,
        'slug' => 'villa-brouillon',
    ]);

    $this->get('/villas/saly/villa-brouillon')->assertNotFound();
    $this->get('/villas/villa-brouillon')->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Plan du site
|--------------------------------------------------------------------------
*/

it('publie un plan du site valide', function () {
    $response = $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $xml = simplexml_load_string($response->content());

    expect($xml)->not->toBeFalse()
        ->and($response->content())->toContain('/villas/saly/villa-teranga');
});

it('n\'expose ni les villas non publiées ni les espaces privés', function () {
    Property::factory()->create([
        'status' => PropertyStatus::Draft,
        'destination_id' => $this->destination->id,
        'slug' => 'villa-secrete',
    ]);

    $sitemap = $this->get('/sitemap.xml')->content();

    expect($sitemap)->not->toContain('villa-secrete')
        ->not->toContain('/admin')
        ->not->toContain('/reservations')
        ->not->toContain('/connexion');
});

it('déclare les deux langues dans le plan du site', function () {
    $sitemap = $this->get('/sitemap.xml')->content();

    expect($sitemap)->toContain('hreflang="fr"')
        ->toContain('hreflang="en"')
        ->toContain('hreflang="x-default"');
});

it('interdit les espaces privés dans robots.txt', function () {
    $robots = file_get_contents(public_path('robots.txt'));

    foreach (['/admin', '/reservations', '/favoris', '/messages', '/webhooks'] as $path) {
        expect($robots)->toContain("Disallow: {$path}");
    }

    expect($robots)->toContain('Sitemap:');
});

/*
|--------------------------------------------------------------------------
| Métadonnées
|--------------------------------------------------------------------------
*/

it('décrit la villa en données structurées', function () {
    $html = $this->get('/villas/saly/villa-teranga')->content();

    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
    $data = json_decode(trim($m[1]), true);

    expect($data['@type'])->toBe('LodgingBusiness')
        ->and($data['name'])->toBe('Villa Teranga')
        ->and($data['address']['addressCountry'])->toBe('SN')
        ->and($data['url'])->toContain('/villas/saly/villa-teranga');
});

it('ne laisse pas indexer les pages privées', function () {
    $customer = User::factory()->create();

    foreach (['/reservations', '/favoris', '/messages', '/notifications'] as $path) {
        $html = $this->actingAs($customer)->get($path)->content();

        expect($html)->toContain('name="robots" content="noindex, nofollow"');
    }
});

it('laisse indexer les pages publiques', function (string $path) {
    $html = $this->get($path)->content();

    expect($html)->not->toContain('noindex')
        ->and($html)->toContain('rel="canonical"');
})->with(['/', '/villas', '/destinations', '/services', '/a-propos', '/contact']);
