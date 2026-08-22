@php
    $destinations = \App\Support\CatalogCache::destinations();
    $email = \App\Models\Setting::get('contact.email');
    $phone = \App\Models\Setting::get('contact.phone');
@endphp

<footer class="mt-20 bg-navy-900 text-white/75">
    <div class="container-page grid gap-10 py-14 md:grid-cols-2 lg:grid-cols-4">
        <div class="flex flex-col gap-4">
            <x-site.logo tone="light" />
            <p class="max-w-xs text-sm leading-relaxed">
                {{ __('Des villas soigneusement sélectionnées sur la Petite Côte du Sénégal, vérifiées une à une.') }}
            </p>
        </div>

        <div class="flex flex-col gap-3">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-white/50">{{ __('Destinations') }}</h2>
            <ul class="flex flex-col gap-2 text-sm">
                @foreach ($destinations as $destination)
                    <li>
                        <a href="{{ route('destinations.show', $destination) }}" class="transition-colors hover:text-white">
                            {{ $destination->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="flex flex-col gap-3">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-white/50">{{ __('Explorer') }}</h2>
            <ul class="flex flex-col gap-2 text-sm">
                <li><a href="{{ route('villas.index') }}" class="transition-colors hover:text-white">{{ __('Toutes les villas') }}</a></li>
                <li><a href="{{ route('services') }}" class="transition-colors hover:text-white">{{ __('Nos services') }}</a></li>
                <li><a href="{{ route('about') }}" class="transition-colors hover:text-white">{{ __('À propos') }}</a></li>
                <li><a href="{{ route('contact') }}" class="transition-colors hover:text-white">{{ __('Déposer ma villa') }}</a></li>
            </ul>
        </div>

        <div class="flex flex-col gap-3">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-white/50">{{ __('Nous joindre') }}</h2>
            <ul class="flex flex-col gap-2 text-sm">
                @if ($email)
                    <li>
                        <a href="mailto:{{ $email }}" class="inline-flex items-center gap-2 transition-colors hover:text-white">
                            <x-ui.icon name="mail" class="size-4 text-white/45" />{{ $email }}
                        </a>
                    </li>
                @endif
                @if ($phone)
                    <li>
                        <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="inline-flex items-center gap-2 transition-colors hover:text-white">
                            <x-ui.icon name="phone" class="size-4 text-white/45" />{{ $phone }}
                        </a>
                    </li>
                @endif
                <li class="inline-flex items-center gap-2">
                    <x-ui.icon name="map-pin" class="size-4 text-white/45" />{{ __('Petite Côte, Sénégal') }}
                </li>
            </ul>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-page py-5 text-xs text-white/50">
            <p>© {{ date('Y') }} Petite Côte Villas. {{ __('Tous droits réservés.') }}</p>
        </div>
    </div>
</footer>
