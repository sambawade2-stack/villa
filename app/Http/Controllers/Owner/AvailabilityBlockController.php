<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Enums\BlockReason;
use App\Exceptions\BookingNotAllowedException;
use App\Exceptions\DatesUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\AvailabilityBlock;
use App\Models\Property;
use App\Services\Booking\AvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Un propriétaire bloque ses propres dates, pour son usage personnel.
 *
 * Un simple blocage de calendrier, jamais une réservation : aucun prix, aucune
 * commission. Toujours posé avec `reason = owner_use`, jamais laissé au choix
 * du propriétaire — `manual` et `maintenance` restent des gestes
 * d'administration.
 */
class AvailabilityBlockController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    public function store(Request $request, Property $property): RedirectResponse
    {
        $this->authorize('manage', $property);

        $data = $request->validate([
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->availability->block(
                $property,
                $data['starts_on'],
                $data['ends_on'],
                BlockReason::OwnerUse,
                $data['note'] ?? null,
                $request->user(),
            );
        } catch (DatesUnavailableException|BookingNotAllowedException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('status', __('Dates bloquées.'));
    }

    public function destroy(Request $request, Property $property, AvailabilityBlock $block): RedirectResponse
    {
        $this->authorize('manage', $property);

        // Un propriétaire ne retire que ses propres blocages : ni ceux posés
        // par l'administration (entretien, autre motif), ni ceux nés d'une
        // réservation cliente.
        abort_unless(
            $block->property_id === $property->id && $block->reason === BlockReason::OwnerUse,
            404
        );

        try {
            $this->availability->unblock($block);
        } catch (BookingNotAllowedException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('Blocage retiré, dates de nouveau disponibles.'));
    }
}
