<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\BlockReason;
use App\Exceptions\BookingNotAllowedException;
use App\Exceptions\DatesUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\AvailabilityBlock;
use App\Models\Property;
use App\Services\Booking\AvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AvailabilityBlockController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    public function store(Request $request, Property $property): RedirectResponse
    {
        $data = $request->validate([
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date'],
            'reason' => ['required', Rule::in([BlockReason::Manual->value, BlockReason::Maintenance->value])],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->availability->block(
                $property,
                $data['starts_on'],
                $data['ends_on'],
                BlockReason::from($data['reason']),
                $data['note'] ?? null,
                $request->user(),
            );
        } catch (DatesUnavailableException|BookingNotAllowedException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('status', __('Période bloquée.'));
    }

    public function destroy(Property $property, AvailabilityBlock $block): RedirectResponse
    {
        abort_unless($block->property_id === $property->id, 404);

        try {
            $this->availability->unblock($block);
        } catch (BookingNotAllowedException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('Blocage retiré, dates de nouveau disponibles.'));
    }
}
