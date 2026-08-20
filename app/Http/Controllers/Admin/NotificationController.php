<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Notifications de l'administration.
 *
 * Écran distinct de celui du client, et non le même dans un autre habillage :
 * ces notifications portent des références de réservation et des montants qui
 * n'ont rien à faire dans le décor du site public.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.notifications', [
            'notifications' => $request->user()->notifications()->paginate(25),
            'unread' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);

        $item->markAsRead();

        $url = $item->data['url'] ?? null;

        return redirect()->to(is_string($url) ? $url : route('admin.notifications.index'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', __('Toutes les notifications sont marquées comme lues.'));
    }
}
