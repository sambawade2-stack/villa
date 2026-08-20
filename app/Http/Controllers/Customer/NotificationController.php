<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('customer.notifications', [
            'notifications' => $request->user()->notifications()->paginate(20),
            'unread' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /** Marque une notification lue et suit son lien. */
    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);

        $item->markAsRead();

        $url = $item->data['url'] ?? null;

        return redirect()->to(is_string($url) ? $url : route('notifications.index'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', __('Toutes vos notifications sont marquées comme lues.'));
    }
}
