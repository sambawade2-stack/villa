<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // En local, le serveur de dev Vite sert le JS/CSS et le rechargement à
        // chaud depuis une autre origine (localhost:5173) : une CSP stricte
        // casserait le rendu. Elle ne s'applique donc qu'en dehors du dev.
        if (! app()->environment('local', 'testing')) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self'",
                "style-src 'self' https://fonts.googleapis.com 'unsafe-inline'",
                'font-src https://fonts.gstatic.com',
                "img-src 'self' data: https:",
                "frame-ancestors 'none'",
            ]));
        }

        return $response;
    }
}
