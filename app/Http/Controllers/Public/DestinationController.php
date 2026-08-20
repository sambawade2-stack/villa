<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use Illuminate\Contracts\View\View;

class DestinationController extends Controller
{
    public function index(): View
    {
        $destinations = Destination::query()
            ->active()
            ->ordered()
            ->withCount(['properties as villas_count' => fn ($q) => $q->published()])
            ->with(['properties' => fn ($q) => $q->published()->with('primaryImage')->limit(1)])
            ->get();

        return view('public.destinations.index', compact('destinations'));
    }

    public function show(Destination $destination): View
    {
        abort_unless($destination->is_active, 404);

        $properties = $destination->properties()
            ->published()
            ->with(['destination', 'primaryImage'])
            ->orderByRaw('rating_avg desc nulls last')
            ->paginate(12)
            ->withQueryString();

        return view('public.destinations.show', compact('destination', 'properties'));
    }
}
