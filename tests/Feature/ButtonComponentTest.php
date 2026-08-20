<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Composant Bouton
|--------------------------------------------------------------------------
|
| Le composant impose `inline-flex`. Tant que cette classe restait dans la
| base, un « hidden sm:inline-flex » passé par l'appelant ne masquait rien :
| l'ordre dans la feuille de styles l'emportait sur l'ordre dans l'attribut, et
| le bouton restait affiché sur mobile. Ces tests verrouillent la règle.
|
*/

it('impose inline-flex quand l\'appelant ne dit rien de l\'affichage', function () {
    $html = Blade::render('<x-ui.button>Réserver</x-ui.button>');

    expect($html)->toContain('inline-flex');
});

it('laisse l\'appelant décider de l\'affichage', function (string $classes) {
    $html = Blade::render('<x-ui.button class="'.$classes.'">Réserver</x-ui.button>');

    // La base ne doit plus imposer son propre display.
    $rendered = preg_match('/class="([^"]*)"/', $html, $m) ? explode(' ', $m[1]) : [];

    expect($rendered)->not->toContain('inline-flex')
        ->and($html)->toContain($classes);
})->with([
    'masqué sur mobile' => 'hidden sm:inline-flex',
    'bloc pleine largeur' => 'block w-full',
    'masqué au-delà du mobile' => 'flex lg:hidden',
]);

it('conserve l\'alignement interne dans tous les cas', function () {
    $html = Blade::render('<x-ui.button class="hidden sm:inline-flex">Réserver</x-ui.button>');

    expect($html)->toContain('items-center')->toContain('justify-center');
});

it('rend un lien quand une destination est fournie', function () {
    $html = Blade::render('<x-ui.button href="/villas">Voir</x-ui.button>');

    expect($html)->toContain('<a href="/villas"')->not->toContain('<button');
});
