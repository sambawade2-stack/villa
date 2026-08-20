<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Property;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function services(): View
    {
        return view('public.services');
    }

    public function about(): View
    {
        return view('public.about', [
            'stats' => [
                'villas' => Property::query()->published()->count(),
                'destinations' => Destination::query()->active()->count(),
                'verified' => Property::query()->published()->where('is_verified', true)->count(),
            ],
        ]);
    }
}
