<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsCustomer;
use App\Http\Middleware\EnsureUserIsOwner;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Derrière Traefik (reverse proxy Dokploy) : sans ça, Laravel ignore
        // l'en-tête X-Forwarded-Proto et génère toutes ses URLs en http://
        // même si le visiteur est en https://, ce que la CSP bloque ensuite.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->append(SecurityHeaders::class);

        // Les webhooks n'ont pas de session : ils s'authentifient par signature.
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'customer' => EnsureUserIsCustomer::class,
            'owner' => EnsureUserIsOwner::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
