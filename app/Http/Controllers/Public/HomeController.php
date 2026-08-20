<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Property;
use App\Support\CatalogCache;
use App\Support\DestinationSummary;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('public.home', [
            'destinations' => $this->destinations(),
            'featured' => $this->featured(),
            'stats' => $this->stats(),
        ]);
    }

    /**
     * Destinations actives, avec le nombre de villas publiées et un visuel.
     *
     * Passe par le cache : le comptage coûte une sous-requête par destination,
     * pour une information qui ne change qu'à la publication d'une villa.
     *
     * @return Collection<int, DestinationSummary>
     */
    private function destinations(): Collection
    {
        return CatalogCache::destinationsWithCounts();
    }

    /**
     * Villas mises en avant, complétées par les mieux notées si besoin.
     *
     * @return EloquentCollection<int, Property>
     */
    private function featured(): EloquentCollection
    {
        $query = fn () => Property::query()
            ->published()
            ->with(['destination', 'primaryImage']);

        $featured = $query()->where('is_featured', true)->latest('published_at')->limit(6)->get();

        if ($featured->count() >= 6) {
            return $featured;
        }

        return $featured->concat(
            $query()
                ->whereNotIn('id', $featured->pluck('id'))
                ->orderByRaw('rating_avg desc nulls last')
                ->limit(6 - $featured->count())
                ->get()
        );
    }

    /** @return array{villas: int, destinations: int, verified: int} */
    private function stats(): array
    {
        return [
            'villas' => Property::query()->published()->count(),
            'destinations' => Destination::query()->active()->count(),
            'verified' => Property::query()->published()->where('is_verified', true)->count(),
        ];
    }
}
