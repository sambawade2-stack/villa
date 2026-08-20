<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', ReviewStatus::Pending->value);

        return view('admin.reviews.index', [
            'reviews' => Review::query()
                ->with(['property:id,name,slug', 'user:id,first_name,last_name', 'booking:id,reference'])
                ->when(in_array($status, ReviewStatus::values(), true), fn ($q) => $q->where('status', $status))
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'status' => $status,
            'counts' => Review::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    /**
     * Modère un avis.
     *
     * La publication déclenche le recalcul de la note de la villa, via les
     * événements du modèle Review — jamais à la main ici.
     */
    public function update(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(ReviewStatus::class)],
            'admin_reply' => ['nullable', 'string', 'max:2000'],
        ]);

        $status = ReviewStatus::from($data['status']);

        $review->update([
            'status' => $status,
            'published_at' => $status === ReviewStatus::Approved ? ($review->published_at ?? now()) : null,
            'admin_reply' => $data['admin_reply'] ?? $review->admin_reply,
            'replied_at' => filled($data['admin_reply'] ?? null) ? now() : $review->replied_at,
        ]);

        return back()->with('status', __('Avis mis à jour.'));
    }
}
