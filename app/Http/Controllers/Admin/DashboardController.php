<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Commission;
use App\Models\ComplianceCheck;
use App\Models\Property;
use App\Models\PropertyOwner;
use App\Models\User;
use App\Support\Money;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $revenue = (int) Booking::query()
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->sum('total_amount');

        return view('admin.dashboard', [
            'stats' => [
                'properties' => Property::query()->count(),
                'published' => Property::query()->published()->count(),
                'drafts' => Property::query()->where('status', PropertyStatus::Draft)->count(),
                'owners' => PropertyOwner::query()->count(),
                'customers' => User::query()->customers()->count(),
                'bookings' => Booking::query()->count(),
                'revenue' => Money::from($revenue),
                'commissions' => Money::from((int) Commission::query()->sum('commission_amount')),
            ],
            // Pièces refusées ou périmées : ce qui appelle une action immédiate.
            'complianceAlerts' => ComplianceCheck::query()
                ->needingAttention()
                ->with('property:id,name,slug')
                ->latest('updated_at')
                ->limit(6)
                ->get(),
            'unverified' => Property::query()->published()->where('is_verified', false)->count(),
            'recentBookings' => Booking::query()
                ->with(['property:id,name,slug', 'user:id,first_name,last_name'])
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }
}
