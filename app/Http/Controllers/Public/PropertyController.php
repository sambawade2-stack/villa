<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchVillasRequest;
use App\Models\Amenity;
use App\Models\Destination;
use App\Models\Property;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class PropertyController extends Controller
{
    public function index(SearchVillasRequest $request): View
    {
        $filters = $request->validated();

        $properties = Property::query()
            ->published()
            ->with(['destination', 'primaryImage'])
            ->inDestination($filters['destination'] ?? null)
            ->availableBetween($filters['checkin'] ?? null, $filters['checkout'] ?? null)
            ->forGuests(isset($filters['guests']) ? (int) $filters['guests'] : null)
            ->withBedroomsAtLeast(isset($filters['bedrooms']) ? (int) $filters['bedrooms'] : null)
            ->when(isset($filters['bathrooms']), fn ($q) => $q->where('bathrooms', '>=', (int) $filters['bathrooms']))
            ->priceBetween(
                isset($filters['price_min']) ? (int) $filters['price_min'] : null,
                isset($filters['price_max']) ? (int) $filters['price_max'] : null,
            )
            ->withAllAmenities($filters['amenities'] ?? [])
            ->when(! empty($filters['verified']), fn ($q) => $q->where('is_verified', true))
            ->tap(fn ($q) => $this->applySort($q, $filters['sort'] ?? 'pertinence'))
            ->paginate(12)
            ->withQueryString();

        return view('public.villas.index', [
            'properties' => $properties,
            'destinations' => Destination::query()->active()->ordered()->get(),
            'amenities' => Amenity::query()->active()->filterable()->ordered()->get(),
            'filters' => $filters,
            'activeCount' => $this->countActiveFilters($filters),
        ]);
    }

    public function show(Property $property): View
    {
        // Une villa non publiée n'existe pas pour le public, même par accès direct.
        abort_unless($property->isPublished(), 404);

        $property->load([
            'destination',
            'images',
            'amenities' => fn ($q) => $q->active()->ordered(),
            'owner',
        ]);

        $reviews = $property->publishedReviews()
            ->with('user:id,first_name,last_name,avatar_path')
            ->latest('published_at')
            ->limit(6)
            ->get();

        $similar = Property::query()
            ->published()
            ->where('destination_id', $property->destination_id)
            ->whereKeyNot($property->getKey())
            ->with(['destination', 'primaryImage'])
            ->orderByRaw('rating_avg desc nulls last')
            ->limit(3)
            ->get();

        return view('public.villas.show', compact('property', 'reviews', 'similar'));
    }

    /**
     * « Pertinence » n'est pas un ordre arbitraire : villas vérifiées d'abord,
     * puis les mieux notées, puis les plus récemment publiées.
     *
     * @param  Builder<Property>  $query
     */
    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'prix-croissant' => $query->orderBy('base_price'),
            'prix-decroissant' => $query->orderByDesc('base_price'),
            'note' => $query->orderByRaw('rating_avg desc nulls last')->orderByDesc('reviews_count'),
            'nouveautes' => $query->orderByDesc('published_at'),
            default => $query
                ->orderByDesc('is_verified')
                ->orderByRaw('rating_avg desc nulls last')
                ->orderByDesc('published_at'),
        };
    }

    /** @param  array<string, mixed>  $filters */
    private function countActiveFilters(array $filters): int
    {
        $counted = ['destination', 'checkin', 'guests', 'bedrooms', 'bathrooms', 'price_min', 'price_max', 'verified'];

        $active = count(array_filter(
            array_intersect_key($filters, array_flip($counted)),
            fn ($value) => $value !== null && $value !== '' && $value !== false,
        ));

        return $active + count($filters['amenities'] ?? []);
    }
}
