<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $properties = Property::query()
            ->with(['destination:id,name,slug', 'owner:id,first_name,last_name', 'primaryImage'])
            ->withCount([
                'complianceChecks as compliance_satisfied_count' => fn ($q) => $q->satisfied(),
                'complianceChecks as compliance_alert_count' => fn ($q) => $q->needingAttention(),
            ])
            ->when($status !== null && in_array($status, PropertyStatus::values(), true),
                fn ($q) => $q->where('status', $status))
            ->search($request->query('q'))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.villas.index', [
            'properties' => $properties,
            'status' => $status,
            'counts' => Property::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }
}
