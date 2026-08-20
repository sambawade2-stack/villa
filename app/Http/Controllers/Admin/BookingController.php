<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        return view('admin.bookings.index', [
            'bookings' => Booking::query()
                ->with(['property:id,name,slug', 'user:id,first_name,last_name,email', 'payment'])
                ->when($status !== null && in_array($status, BookingStatus::values(), true),
                    fn ($q) => $q->where('status', $status))
                ->when($request->filled('q'), function ($q) use ($request) {
                    $like = '%'.str_replace('%', '\%', (string) $request->query('q')).'%';
                    $q->where(fn ($sub) => $sub
                        ->where('reference', 'ilike', $like)
                        ->orWhereHas('user', fn ($u) => $u->where('last_name', 'ilike', $like)->orWhere('email', 'ilike', $like))
                        ->orWhereHas('property', fn ($p) => $p->where('name', 'ilike', $like)));
                })
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'status' => $status,
            'counts' => Booking::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(Booking $booking): View
    {
        return view('admin.bookings.show', [
            'booking' => $booking->load([
                'property.destination', 'property.owner', 'user',
                'payments', 'commission', 'guests', 'review',
            ]),
        ]);
    }
}
