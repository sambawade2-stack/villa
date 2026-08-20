<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Property;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Plan du site.
 *
 * Ne liste que ce qui est réellement public et indexable : villas publiées,
 * destinations actives, pages éditoriales. Les espaces client et administration
 * n'y figurent pas — ils sont d'ailleurs marqués noindex.
 *
 * Mis en cache une heure : un moteur peut l'appeler souvent, et la requête
 * balaie tout le catalogue.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), fn () => $this->build());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function build(): string
    {
        $urls = [];

        // Pages fixes. La priorité décroît avec la distance à la conversion.
        foreach ([
            ['home', 1.0, 'daily'],
            ['villas.index', 0.9, 'daily'],
            ['destinations.index', 0.8, 'weekly'],
            ['services', 0.6, 'monthly'],
            ['about', 0.5, 'monthly'],
            ['contact', 0.5, 'monthly'],
        ] as [$name, $priority, $frequency]) {
            $urls[] = [
                'loc' => route($name),
                'priority' => $priority,
                'changefreq' => $frequency,
                'lastmod' => null,
            ];
        }

        foreach (Destination::query()->active()->ordered()->get() as $destination) {
            $urls[] = [
                'loc' => route('destinations.show', $destination),
                'priority' => 0.8,
                'changefreq' => 'weekly',
                'lastmod' => $destination->updated_at,
            ];
        }

        Property::query()
            ->published()
            ->with('destination:id,slug')
            ->chunk(200, function ($properties) use (&$urls) {
                foreach ($properties as $property) {
                    $urls[] = [
                        'loc' => route('villas.show', [$property->destination, $property]),
                        'priority' => 0.7,
                        'changefreq' => 'weekly',
                        'lastmod' => $property->updated_at,
                    ];
                }
            });

        return view('seo.sitemap', ['urls' => $urls])->render();
    }
}
