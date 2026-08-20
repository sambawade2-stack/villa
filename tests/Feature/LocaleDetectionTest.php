<?php

declare(strict_types=1);

use App\Models\User;

/*
|--------------------------------------------------------------------------
| Choix de la langue
|--------------------------------------------------------------------------
|
| La négociation par le navigateur est restée désactivée tant que la
| traduction anglaise était partielle : un parc majoritairement réglé en
| anglais aurait reçu une version incomplète. lang/en.json couvrant désormais
| tout le site public, elle est réactivée — ces tests fixent l'ordre de
| priorité qui en résulte.
|
*/

it('sert le français à un navigateur francophone', function () {
    $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')->get(route('home'));

    expect(app()->getLocale())->toBe('fr');
});

it('sert l\'anglais à un navigateur anglophone', function () {
    $this->withHeader('Accept-Language', 'en-US,en;q=0.9')->get(route('home'));

    expect(app()->getLocale())->toBe('en');
});

it('retombe sur le français pour une langue non prise en charge', function () {
    $this->withHeader('Accept-Language', 'de-DE,de;q=0.9')->get(route('home'));

    expect(app()->getLocale())->toBe('fr');
});

it('fait primer le paramètre d\'URL sur le navigateur', function () {
    // C'est ce paramètre qui donne à chaque page une adresse stable par langue,
    // sans laquelle hreflang n'aurait aucun sens.
    $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')->get(route('home', ['lang' => 'en']));

    expect(app()->getLocale())->toBe('en');
});

it('retient le choix fait dans l\'URL pour la suite de la navigation', function () {
    $this->get(route('home', ['lang' => 'en']));

    expect(session('locale'))->toBe('en');

    $this->get(route('home'));

    expect(app()->getLocale())->toBe('en');
});

it('fait primer la préférence du compte sur le navigateur', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)->withHeader('Accept-Language', 'fr-FR')->get(route('home'));

    expect(app()->getLocale())->toBe('en');
});

it('ignore une langue non prise en charge stockée en session', function () {
    session(['locale' => 'de']);

    $this->get(route('home'));

    expect(app()->getLocale())->toBe('fr');
});

it('ignore un paramètre de langue inconnu', function () {
    $this->get(route('home', ['lang' => 'zz']));

    expect(app()->getLocale())->toBe('fr')
        ->and(session('locale'))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Ce que voit un moteur de recherche
|--------------------------------------------------------------------------
*/

it('déclare une adresse distincte par langue', function () {
    $html = $this->get(route('home'))->content();

    expect($html)->toContain('hreflang="fr"')
        ->toContain('hreflang="en"')
        ->toContain('hreflang="x-default"')
        // hreflang doit désigner la page équivalente, jamais l'action de bascule.
        ->not->toContain('hreflang="en"'.PHP_EOL.'              href="'.route('locale.switch', 'en').'"');
});

it('fait pointer la canonique sur la version servie', function () {
    $fr = $this->get(route('home'))->content();
    $en = $this->get(route('home', ['lang' => 'en']))->content();

    expect($fr)->toContain('<link rel="canonical" href="'.route('home').'">')
        ->and($en)->toContain('lang=en">');
});

it('traduit réellement la page d\'accueil', function () {
    $this->get(route('home', ['lang' => 'en']))
        ->assertOk()
        ->assertSee('Find your ideal villa on the Petite Côte')
        ->assertSee('Popular destinations')
        ->assertDontSee('Trouvez la villa idéale');
});
