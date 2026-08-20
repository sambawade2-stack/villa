<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Exceptions\BookingNotAllowedException;
use App\Exceptions\DatesUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Property;
use App\Models\Setting;
use App\Services\Booking\BookingService;
use App\Services\Payment\PaymentManager;
use App\Services\Payment\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly PaymentService $payments,
        private readonly PaymentManager $gateways,
    ) {}

    public function index(Request $request): View
    {
        $bookings = $request->user()->bookings()
            ->with(['property.destination', 'property.primaryImage', 'payment'])
            ->latest('checkin_date')
            ->paginate(10);

        return view('customer.bookings.index', compact('bookings'));
    }

    /**
     * Tient les dates.
     *
     * Rien du prix n'est accepté depuis le formulaire : seuls les dates et le
     * nombre de voyageurs traversent, le montant est recalculé par le service.
     */
    public function store(Request $request, Property $property): RedirectResponse
    {
        $data = $request->validate([
            'checkin' => ['required', 'date_format:Y-m-d'],
            'checkout' => ['required', 'date_format:Y-m-d', 'after:checkin'],
            'guests' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        try {
            $booking = $this->bookings->hold(
                $property, $request->user(),
                $data['checkin'], $data['checkout'], (int) $data['guests'],
            );
        } catch (DatesUnavailableException|BookingNotAllowedException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('bookings.checkout', $booking);
    }

    public function checkout(Request $request, Booking $booking): View|RedirectResponse
    {
        $this->authorizeOwnership($request, $booking);

        if (! $booking->isPending()) {
            return redirect()->route('bookings.show', $booking);
        }

        if ($booking->holdHasExpired()) {
            $this->bookings->cancel($booking, reason: __('Délai de paiement dépassé.'));

            return redirect()->route('villas.show', [$booking->property->destination, $booking->property])
                ->with('error', __('Le délai de paiement est écoulé, les dates ont été libérées.'));
        }

        return view('customer.bookings.checkout', [
            'booking' => $booking->load('property.destination'),
            'gateways' => $this->gateways->available(),
        ]);
    }

    public function pay(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwnership($request, $booking);

        abort_unless($booking->isPending(), 404);

        $data = $request->validate(['gateway' => ['required', 'string', 'max:32']]);

        $payment = $this->payments->initiate($booking, $data['gateway']);

        if ($payment->checkout_url !== null) {
            return redirect()->away($payment->checkout_url);
        }

        return redirect()->route('bookings.show', $booking)
            ->with('status', __('Votre demande est enregistrée. Réglez selon les instructions ci-dessous : nous confirmons dès réception.'));
    }

    public function show(Request $request, Booking $booking): View
    {
        $this->authorizeOwnership($request, $booking);

        return view('customer.bookings.show', [
            'booking' => $booking->load(['property.destination', 'property.primaryImage', 'payment', 'review']),
            'instructions' => Setting::get('payment.manual_instructions'),
        ]);
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwnership($request, $booking);

        try {
            $this->bookings->cancel($booking, $request->user(), __('Annulation demandée par le client.'));
        } catch (BookingNotAllowedException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('Réservation annulée. Les dates sont de nouveau disponibles.'));
    }

    /** Un client n'accède qu'à ses propres réservations. 404, jamais 403. */
    private function authorizeOwnership(Request $request, Booking $booking): void
    {
        abort_unless($booking->user_id === $request->user()->id, 404);
    }
}
