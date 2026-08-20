<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\OwnerStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PropertyOwner;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Propriétaires.
 *
 * En v1 ce sont des contacts internes, sans compte : rien ici n'est visible du
 * public, et le téléphone comme l'adresse ne quittent pas cet écran.
 */
class PropertyOwnerController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.owners.index', [
            'owners' => PropertyOwner::query()
                ->withCount('properties')
                ->search($request->query('q'))
                ->orderBy('last_name')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function show(PropertyOwner $owner): View
    {
        $owner->load(['properties.destination', 'properties.primaryImage']);

        $revenue = (int) Booking::query()
            ->whereIn('property_id', $owner->properties->pluck('id'))
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->sum('total_amount');

        return view('admin.owners.show', [
            'owner' => $owner,
            'bookingsCount' => Booking::query()->whereIn('property_id', $owner->properties->pluck('id'))->count(),
            'revenue' => Money::from($revenue),
            'payout' => Money::from((int) $owner->commissions()->sum('owner_payout_amount')),
        ]);
    }

    public function create(): View
    {
        return view('admin.owners.form', ['owner' => new PropertyOwner]);
    }

    public function store(Request $request): RedirectResponse
    {
        $owner = PropertyOwner::create($this->validated($request));

        return redirect()->route('admin.owners.show', $owner)
            ->with('status', __('Propriétaire enregistré.'));
    }

    public function edit(PropertyOwner $owner): View
    {
        return view('admin.owners.form', compact('owner'));
    }

    public function update(Request $request, PropertyOwner $owner): RedirectResponse
    {
        $owner->update($this->validated($request, $owner));

        return redirect()->route('admin.owners.show', $owner)
            ->with('status', __('Fiche mise à jour.'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?PropertyOwner $owner = null): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:32'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:190',
                Rule::unique('property_owners', 'email')->ignore($owner?->id)->whereNull('deleted_at')],
            'city' => ['nullable', 'string', 'max:120'],
            'internal_address' => ['nullable', 'string', 'max:500'],
            'internal_notes' => ['nullable', 'string', 'max:4000'],
            'status' => ['required', Rule::enum(OwnerStatus::class)],
        ]);
    }
}
