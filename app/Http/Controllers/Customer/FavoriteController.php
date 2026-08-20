<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $properties = $request->user()
            ->favoriteProperties()
            ->published()
            ->with(['destination', 'primaryImage'])
            ->orderByDesc('favorites.created_at')
            ->paginate(12);

        return view('customer.favorites', compact('properties'));
    }

    /**
     * Ajoute ou retire une villa des favoris.
     *
     * Un client ne peut agir que sur ses propres favoris : l'identifiant vient
     * de la session, jamais de la requête.
     */
    public function toggle(Request $request, Property $property): RedirectResponse
    {
        abort_unless($property->isPublished(), 404);

        $existing = $request->user()->favorites()->where('property_id', $property->id)->first();

        if ($existing) {
            $existing->delete();
            $message = __('Villa retirée de vos favoris.');
        } else {
            $request->user()->favorites()->create(['property_id' => $property->id]);
            $message = __('Villa ajoutée à vos favoris.');
        }

        return back()->with('status', $message);
    }
}
