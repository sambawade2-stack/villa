<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Services\Compliance\ComplianceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Espace propriétaire : chaque propriétaire ne voit que ses propres villas.
 *
 * Lecture seule sur tout ce qui touche aux réservations et aux paiements —
 * ces circuits restent pilotés par l'administration. Seul le blocage de
 * dates pour usage personnel est un geste que le propriétaire pose lui-même,
 * voir Owner\AvailabilityBlockController.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly ComplianceService $compliance) {}

    public function index(Request $request): View
    {
        $properties = $request->user()->propertyOwner
            ?->properties()
            ->with('destination')
            ->withCount(['bookings as upcoming_bookings_count' => fn ($q) => $q
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
                ->where('checkout_date', '>=', now()->toDateString())])
            ->orderBy('name')
            ->get() ?? collect();

        return view('owner.dashboard', ['properties' => $properties]);
    }

    public function show(Request $request, Property $property): View
    {
        $this->authorize('manage', $property);

        return view('owner.villa', [
            'property' => $property->load('destination'),
            'compliance' => $this->compliance->summary($property),
            'bookings' => $property->bookings()
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Completed])
                ->latest('checkin_date')
                ->paginate(10),
            'blocks' => $property->availabilityBlocks()
                ->where('ends_on', '>=', now()->toDateString())
                ->orderBy('starts_on')
                ->get(),
        ]);
    }
}
