<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, config('app.available_locales', [])), 404);

        $request->session()->put('locale', $locale);

        // La préférence suit le compte quand il y en a un.
        $request->user()?->update(['locale' => $locale]);

        return back();
    }
}
