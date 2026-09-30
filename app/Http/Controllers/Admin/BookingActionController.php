<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\BookingNotAllowedException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Gestes de l'administrateur sur une réservation.
 *
 * Confirmer un règlement hors ligne est un acte humain : il est tracé,
 * attribué, et ne peut pas être rejoué.
 */
class BookingActionController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly BookingService $bookings,
    ) {}

    public function confirmPayment(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        $payment = $booking->payment;

        if ($payment === null) {
            return back()->with('error', __('Aucun paiement n\'a été initié pour cette réservation.'));
        }

        if ($payment->status->isSettled()) {
            return back()->with('error', __('Ce paiement est déjà constaté.'));
        }

        try {
            $this->payments->markPaid($payment, $request->user(), $data['note'] ?? null);
        } catch (BookingNotAllowedException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('Règlement constaté, réservation confirmée.'));
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        try {
            $this->bookings->cancel($booking, $request->user(), $data['reason']);
        } catch (BookingNotAllowedException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('Réservation annulée, dates libérées.'));
    }
}
