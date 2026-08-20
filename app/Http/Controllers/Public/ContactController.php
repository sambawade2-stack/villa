<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessage;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public const SUBJECTS = ['reservation', 'proprietaire', 'services', 'autre'];

    public function create(): View
    {
        return view('public.contact');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'subject' => ['required', 'string', 'in:'.implode(',', self::SUBJECTS)],
            'message' => ['required', 'string', 'min:20', 'max:4000'],
            // Champ leurre : invisible pour un humain, rempli par les robots.
            'website' => ['nullable', 'size:0'],
        ]);

        Mail::to(Setting::get('contact.email', config('mail.from.address')))
            ->send(new ContactMessage([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'subject' => __('contact.subjects.'.$data['subject']),
                'message' => $data['message'],
            ]));

        return back()->with('status', __('Merci, votre message est parti. Nous répondons sous 2 heures ouvrées.'));
    }
}
