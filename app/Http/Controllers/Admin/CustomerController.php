<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $like = '%'.str_replace('%', '\%', (string) $request->query('q')).'%';

        return view('admin.customers.index', [
            'customers' => User::query()
                ->customers()
                ->withCount(['bookings', 'favorites', 'reviews'])
                ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                    ->where('first_name', 'ilike', $like)
                    ->orWhere('last_name', 'ilike', $like)
                    ->orWhere('email', 'ilike', $like)
                    ->orWhere('phone', 'ilike', $like)))
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }
}
