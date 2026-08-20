<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Destination;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Cache du catalogue.
 *
 * Deux jeux de données reviennent sur presque chaque page : la liste des
 * destinations, et la même enrichie du nombre de villas et d'un visuel. Le
 * comptage coûte une sous-requête par destination — 35 ms mesurés sur 20 000
 * villas — pour une information qui ne change qu'à la publication d'une villa.
 *
 * ATTENTION — ce cache ne stocke que des tableaux, jamais des modèles.
 * Laravel 13 durcit la désérialisation du cache : `serializable_classes` vaut
 * false par défaut, ce qui interdit toute reconstruction d'objet et protège
 * des chaînes de gadgets si APP_KEY venait à fuir. Y mettre une collection
 * Eloquent ne lève aucune erreur à l'écriture : la relecture rend simplement
 * un __PHP_Incomplete_Class, et la page casse plus loin. On garde donc la
 * protection et on met en cache des attributs bruts, que Destination::hydrate()
 * reconstitue sans toucher la base.
 *
 * L'invalidation est déclenchée par les modèles Property et Destination, jamais
 * laissée à une durée de vie : un cache qu'on attend voir expirer finit par mentir.
 */
final class CatalogCache
{
    private const KEY_LIST = 'catalog.destinations.list';

    private const KEY_FEATURED = 'catalog.destinations.featured';

    private const TTL_MINUTES = 60;

    /**
     * Destinations actives, pour la navigation, le pied de page et la recherche.
     *
     * @return Collection<int, Destination>
     */
    public static function destinations(): Collection
    {
        $rows = Cache::remember(
            self::KEY_LIST,
            now()->addMinutes(self::TTL_MINUTES),
            fn () => Destination::query()->active()->ordered()->get()
                ->map(fn (Destination $d) => $d->getAttributes())
                ->all(),
        );

        // hydrate() reconstruit des modèles complets, casts compris, sans requête.
        return Destination::hydrate($rows);
    }

    /**
     * Destinations avec le nombre de villas publiées et un visuel.
     *
     * @return Collection<int, DestinationSummary>
     */
    public static function destinationsWithCounts(): Collection
    {
        $rows = Cache::remember(
            self::KEY_FEATURED,
            now()->addMinutes(self::TTL_MINUTES),
            function () {
                return Destination::query()
                    ->active()
                    ->ordered()
                    ->withCount(['properties as villas_count' => fn ($q) => $q->published()])
                    ->with(['properties' => fn ($q) => $q->published()
                        ->with('primaryImage')
                        ->latest('published_at')
                        ->limit(1),
                    ])
                    ->get()
                    ->map(function (Destination $destination) {
                        $image = $destination->properties->first()?->primaryImage;

                        return [
                            // Les URL sont calculées ici : le cache ne porte que
                            // des chaînes, et la vue n'a plus rien à résoudre.
                            'attributes' => $destination->getAttributes(),
                            'count' => (int) $destination->getAttribute('villas_count'),
                            'image_url' => $image?->url('card'),
                            'srcset' => $image?->srcset('thumb', 'card') ?: null,
                        ];
                    })
                    ->all();
            },
        );

        return collect($rows)->map(fn (array $row) => new DestinationSummary(
            destination: Destination::hydrate([$row['attributes']])->firstOrFail(),
            count: $row['count'],
            imageUrl: $row['image_url'],
            srcset: $row['srcset'],
        ));
    }

    public static function flush(): void
    {
        Cache::forget(self::KEY_LIST);
        Cache::forget(self::KEY_FEATURED);
    }
}
