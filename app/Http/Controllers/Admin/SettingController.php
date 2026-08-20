<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /** Réglages modifiables et leur type, pour la validation comme pour l'affichage. */
    private const EDITABLE = [
        'platform.commission_rate' => ['numeric', 'commission'],
        'platform.service_fee_rate' => ['numeric', 'pricing'],
        'booking.hold_minutes' => ['integer', 'booking'],
        'booking.cancellation_full_refund_days' => ['integer', 'booking'],
        'booking.cancellation_half_refund_days' => ['integer', 'booking'],
        'contact.email' => ['email', 'contact'],
        'contact.phone' => ['string', 'contact'],
        'contact.whatsapp' => ['string', 'contact'],
    ];

    public function edit(): View
    {
        return view('admin.settings', [
            'settings' => collect(self::EDITABLE)->map(fn (array $meta, string $key) => [
                'key' => $key,
                'type' => $meta[0],
                'group' => $meta[1],
                'value' => Setting::get($key),
                'description' => Setting::where('key', $key)->value('description'),
            ])->groupBy('group'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];

        foreach (self::EDITABLE as $key => [$type]) {
            $field = str_replace('.', '__', $key);

            $rules[$field] = match ($type) {
                'numeric' => ['required', 'numeric', 'min:0', 'max:100'],
                'integer' => ['required', 'integer', 'min:0', 'max:100000'],
                'email' => ['required', 'email', 'max:190'],
                default => ['nullable', 'string', 'max:190'],
            };
        }

        $data = $request->validate($rules);

        foreach (self::EDITABLE as $key => [$type, $group]) {
            $value = $data[str_replace('.', '__', $key)] ?? null;

            Setting::put($key, match ($type) {
                'numeric' => (float) $value,
                'integer' => (int) $value,
                default => $value,
            }, $group);
        }

        Setting::flushCache();

        return back()->with('status', __('Réglages enregistrés.'));
    }
}
