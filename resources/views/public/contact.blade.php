@php
    use App\Http\Controllers\Public\ContactController;
    use App\Models\Setting;

    $email = Setting::get('contact.email');
    $phone = Setting::get('contact.phone');
    $whatsapp = Setting::get('contact.whatsapp');
@endphp

<x-layouts.public
    :title="__('Contact — Petite Côte Villas')"
    :description="__('Une question sur une villa, un service, ou l\'envie de nous confier votre bien ? Écrivez-nous, nous répondons sous deux heures ouvrées.')"
>
    <div class="border-b border-stone-200 bg-stone-50">
        <div class="container-page py-10">
            <x-ui.breadcrumb :items="[['label' => __('Accueil'), 'url' => route('home')], ['label' => __('Contact')]]" />
            <h1 class="mt-3 text-3xl text-navy-900 lg:text-display">{{ __('Nous contacter') }}</h1>
            <p class="mt-2 max-w-2xl text-navy-500">
                {{ __('Réservation, service sur mesure, ou villa à confier : une seule équipe, sur place, vous répond.') }}
            </p>
        </div>
    </div>

    <div class="container-page grid gap-10 py-12 lg:grid-cols-[1fr_20rem] lg:gap-14">
        <div>
            @if (session('status'))
                <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
            @endif

            <form method="POST" action="{{ route('contact.store') }}" class="flex flex-col gap-5">
                @csrf

                {{-- Leurre anti-robots : masqué à l'écran et retiré de l'ordre de tabulation. --}}
                <div class="hidden" aria-hidden="true">
                    <label for="website">{{ __('Ne pas remplir') }}</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-ui.input name="name" :label="__('Votre nom')" required
                                :value="old('name', auth()->user()?->full_name)" autocomplete="name" />
                    <x-ui.input type="email" name="email" :label="__('Adresse e-mail')" required
                                :value="old('email', auth()->user()?->email)" autocomplete="email" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-ui.input type="tel" name="phone" :label="__('Téléphone (facultatif)')"
                                :value="old('phone', auth()->user()?->phone)" placeholder="+221 77 000 00 00" />

                    <x-ui.select name="subject" :label="__('Votre demande')" required>
                        @foreach (ContactController::SUBJECTS as $subject)
                            <option value="{{ $subject }}" @selected(old('subject') === $subject)>
                                {{ __('contact.subjects.'.$subject) }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="message" class="text-xs font-semibold uppercase tracking-wider text-navy-500">
                        {{ __('Votre message') }}
                    </label>
                    <textarea id="message" name="message" rows="7" required minlength="20" maxlength="4000"
                              class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-navy-900 placeholder:text-navy-300
                                     {{ $errors->has('message') ? 'border-danger-500' : 'border-stone-300 hover:border-stone-400 focus:border-navy-500' }}"
                              placeholder="{{ __('Décrivez votre projet : dates, nombre de voyageurs, destination souhaitée…') }}">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="text-xs text-danger-700">{{ $message }}</p>
                    @enderror
                </div>

                <x-ui.button type="submit" size="lg" class="self-start px-8">{{ __('Envoyer le message') }}</x-ui.button>
            </form>
        </div>

        <aside class="flex flex-col gap-4">
            <div class="rounded-card border border-stone-200 bg-white p-5">
                <h2 class="text-base font-semibold text-navy-900">{{ __('Directement') }}</h2>
                <ul class="mt-3 flex flex-col gap-3 text-sm">
                    @if ($phone)
                        <li>
                            <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="inline-flex items-center gap-2.5 text-navy-700 hover:text-navy-900">
                                <span class="flex size-9 items-center justify-center rounded-lg bg-navy-50 text-navy-600">
                                    <x-ui.icon name="phone" class="size-4" />
                                </span>
                                {{ $phone }}
                            </a>
                        </li>
                    @endif
                    @if ($whatsapp)
                        <li>
                            <a href="https://wa.me/{{ preg_replace('/\D+/', '', $whatsapp) }}" rel="noopener"
                               class="inline-flex items-center gap-2.5 text-navy-700 hover:text-navy-900">
                                <span class="flex size-9 items-center justify-center rounded-lg bg-success-50 text-success-500">
                                    <x-ui.icon name="message-circle" class="size-4" />
                                </span>
                                {{ __('WhatsApp') }}
                            </a>
                        </li>
                    @endif
                    @if ($email)
                        <li>
                            <a href="mailto:{{ $email }}" class="inline-flex items-center gap-2.5 text-navy-700 hover:text-navy-900">
                                <span class="flex size-9 items-center justify-center rounded-lg bg-amber-50 text-amber-500">
                                    <x-ui.icon name="mail" class="size-4" />
                                </span>
                                {{ $email }}
                            </a>
                        </li>
                    @endif
                </ul>
            </div>

            <div class="rounded-card border border-stone-200 bg-stone-50 p-5">
                <h2 class="text-base font-semibold text-navy-900">{{ __('Vous possédez une villa ?') }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-navy-500">
                    {{ __('Choisissez « Confier ma villa » : nous organisons une visite, la photographie et la mise en ligne. La gestion des réservations reste chez nous.') }}
                </p>
            </div>
        </aside>
    </div>
</x-layouts.public>
