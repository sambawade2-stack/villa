<?php

declare(strict_types=1);

use App\Models\User;

it('sert le français par défaut, quelle que soit la langue du navigateur', function () {
    // Un navigateur réglé en anglais ne suffit pas à basculer le site : la
    // traduction anglaise est encore partielle, et le marché est francophone.
    $this->withHeader('Accept-Language', 'en-US,en;q=0.9')->get(route('home'));

    expect(app()->getLocale())->toBe('fr');
});

it('respecte le choix explicite du visiteur', function () {
    $this->get(route('locale.switch', 'en'));
    $this->get(route('home'));

    expect(app()->getLocale())->toBe('en');
});

it('respecte la préférence enregistrée sur le compte', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)->get(route('home'));

    expect(app()->getLocale())->toBe('en');
});

it('ignore une langue non prise en charge stockée en session', function () {
    session(['locale' => 'de']);

    $this->get(route('home'));

    expect(app()->getLocale())->toBe('fr');
});
