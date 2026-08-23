<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Constats de l'audit de sécurité du 23 août 2026 — corrections Phase 1
|--------------------------------------------------------------------------
*/

it('ajoute les en-têtes de sécurité à chaque réponse', function () {
    $this->get('/')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});

it('n\'impose pas la CSP stricte en dev/test, pour ne pas casser le rechargement à chaud de Vite', function () {
    expect(app()->environment('local', 'testing'))->toBeTrue();

    $this->get('/')->assertHeaderMissing('Content-Security-Policy');
});

it('force le cookie de session en HTTPS par défaut, sauf override explicite', function () {
    expect(config('session.secure'))->not->toBeFalsy();
});

it('n\'active jamais APP_DEBUG par défaut dans le modèle d\'environnement', function () {
    expect(file_get_contents(base_path('.env.example')))
        ->toContain('APP_DEBUG=false')
        ->not->toContain('APP_DEBUG=true');
});

it('limite le nombre de recherches de villas par minute', function () {
    foreach (range(1, 60) as $i) {
        $this->get(route('villas.index'));
    }

    $this->get(route('villas.index'))->assertStatus(429);
});
