<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchVillasRequest;
use App\Models\Amenity;
use App\Models\Destination;
use App\Models\Property;
use App\Services\Pricing\PricingService;
use App\Services\Pricing\Quote;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    /** Ancienne adresse à un seul segment : redirection permanente. */
    public function legacyShow(Property $property): RedirectResponse
    {
        abort_unless($property->isPublished(), 404);

        return redirect()->route('villas.show', [$property->destination, $property], 301);
    }

    public function index(SearchVillasRequest $request): View
    {
        $filters = $request->validated();

        $properties = Property::query()
            ->published()
            ->with(['destination', 'primaryImage', 'amenities'])
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

    /**
     * Fiche villa, à l'adresse canonique /villas/{destination}/{villa}.
     *
     * La destination fait partie de l'URL parce qu'elle fait partie de la
     * recherche : « villa Saly » est la requête réelle des voyageurs. Si elle ne
     * correspond pas à la villa — lien ancien, villa déplacée — on redirige en
     * 301 vers la bonne adresse plutôt que de servir deux URL pour une page.
     */
    public function show(Request $request, Destination $destination, Property $property, PricingService $pricing): View|RedirectResponse
    {
        // Une villa non publiée n'existe pas pour le public, même par accès direct.
        abort_unless($property->isPublished(), 404);

        if ($property->destination_id !== $destination->id) {
            return redirect()->route('villas.show', [$property->destination, $property], 301);
        }

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

        [$quote, $requestedDates] = $this->quoteFor($request, $property, $pricing);

        return view('public.villas.show', compact('property', 'reviews', 'similar', 'quote', 'requestedDates'));
    }

    /**
     * Devis à afficher sur la fiche.
     *
     * Si le visiteur arrive avec des dates, on chiffre ces dates. Sinon on
     * affiche un devis indicatif sur sept nuits, clairement annoncé comme tel :
     * un total sans dates serait un chiffre sorti de nulle part.
     *
     * @return array{0: Quote|null, 1: bool}
     */
    private function quoteFor(Request $request, Property $property, PricingService $pricing): array
    {
        $checkin = $request->query('checkin');
        $checkout = $request->query('checkout');
        $guests = (int) $request->query('guests', 2);

        if (is_string($checkin) && is_string($checkout)) {
            try {
                return [$pricing->quote($property, $checkin, $checkout, max(1, $guests)), true];
            } catch (\Throwable) {
                // Dates illisibles ou incohérentes dans l'URL : on retombe sur
                // l'indicatif plutôt que de casser la page.
            }
        }

        try {
            return [$pricing->indicativeQuote($property), false];
        } catch (\Throwable) {
            return [null, false];
        }
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
