@props(['transparent' => false])

@php
    $links = [
        ['label' => __('Accueil'), 'route' => 'home', 'url' => route('home')],
        ['label' => __('Destinations'), 'route' => 'destinations.*', 'url' => route('destinations.index')],
        ['label' => __('Villas'), 'route' => 'villas.*', 'url' => route('villas.index')],
        ['label' => __('Services'), 'route' => 'services', 'url' => route('services')],
        ['label' => __('À propos'), 'route' => 'about', 'url' => route('about')],
    ];

    $tone = $transparent ? 'light' : 'navy';
@endphp

<header x-data="{ open: false }"
        @class([
            'z-40',
            'absolute inset-x-0 top-0' => $transparent,
            'sticky top-0 border-b border-stone-200 bg-white' => ! $transparent,
        ])>
    <div class="container-page flex h-[4.5rem] items-center justify-between gap-6">
        <x-site.logo :tone="$tone" />

        <nav class="hidden items-center gap-7 xl:flex" aria-label="{{ __('Navigation principale') }}">
            @foreach ($links as $link)
                @php $active = request()->routeIs($link['route']); @endphp
                <a href="{{ $link['url'] }}"
                   @if ($active) aria-current="page" @endif
                   @class([
                       'text-sm transition-colors',
                       'font-semibold' => $active,
                       'font-medium' => ! $active,
                       ($active ? 'text-white' : 'text-white/75 hover:text-white') => $transparent,
                       ($active ? 'text-navy-900' : 'text-navy-500 hover:text-navy-900') => ! $transparent,
                   ])>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2.5">
            @auth
                <a href="{{ route('favorites.index') }}"
                   @class([
                       'relative hidden rounded-lg p-2 transition-colors sm:block',
                       'text-white/80 hover:bg-white/10 hover:text-white' => $transparent,
                       'text-navy-500 hover:bg-stone-100 hover:text-navy-900' => ! $transparent,
                   ])>
                    <span class="sr-only">{{ __('Mes favoris') }}</span>
                    <x-ui.icon name="heart" class="size-5" />
                    @php $favorites = auth()->user()->favorites()->count(); @endphp
                    @if ($favorites > 0)
                        <span class="absolute right-0.5 top-0.5 flex size-4 items-center justify-center rounded-full bg-amber-400 text-[0.6rem] font-bold text-navy-900 tabular">
                            {{ $favorites > 9 ? '9+' : $favorites }}
                        </span>
                    @endif
                </a>
            @endauth

            <x-ui.button :href="route('contact')" :variant="$transparent ? 'outline-light' : 'outline'" size="md"
                         class="hidden sm:inline-flex">
                {{ __('Déposer ma villa') }}
            </x-ui.button>

            <x-site.locale-switcher :tone="$tone" class="hidden md:flex" />

            @auth
                <form method="POST" action="{{ route('logout') }}" class="hidden xl:block">
                    @csrf
                    <button type="submit"
                            @class([
                                'text-sm font-medium transition-colors',
                                'text-white/75 hover:text-white' => $transparent,
                                'text-navy-500 hover:text-navy-900' => ! $transparent,
                            ])>
                        {{ __('Déconnexion') }}
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}"
                   @class([
                       'hidden text-sm font-medium transition-colors xl:block',
                       'text-white/75 hover:text-white' => $transparent,
                       'text-navy-500 hover:text-navy-900' => ! $transparent,
                   ])>
                    {{ __('Connexion') }}
                </a>
            @endauth

            <button type="button" @click="open = ! open" :aria-expanded="open" aria-controls="nav-mobile"
                    @class([
                        'rounded-lg p-2 xl:hidden',
                        'text-white hover:bg-white/10' => $transparent,
                        'text-navy-700 hover:bg-stone-100' => ! $transparent,
                    ])>
                <span class="sr-only">{{ __('Ouvrir le menu') }}</span>
                <x-ui.icon name="menu" x-show="! open" class="size-6" />
                <x-ui.icon name="x" x-show="open" x-cloak class="size-6" />
            </button>
        </div>
    </div>

    <div id="nav-mobile" x-show="open" x-cloak x-collapse
         class="border-t border-stone-200 bg-white xl:hidden">
        <nav class="container-page flex flex-col py-3" aria-label="{{ __('Navigation principale') }}">
            @foreach ($links as $link)
                <a href="{{ $link['url'] }}"
                   @class(['py-2.5 text-sm', 'font-semibold text-navy-900' => request()->routeIs($link['route']), 'text-navy-600' => ! request()->routeIs($link['route'])])>
                    {{ $link['label'] }}
                </a>
            @endforeach

            <div class="mt-3 flex flex-wrap items-center gap-3 border-t border-stone-200 pt-4">
                <x-ui.button :href="route('contact')" variant="outline" size="sm">{{ __('Déposer ma villa') }}</x-ui.button>
                @auth
                    <x-ui.button :href="route('favorites.index')" variant="ghost" size="sm" icon="heart">{{ __('Favoris') }}</x-ui.button>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.button type="submit" variant="ghost" size="sm">{{ __('Déconnexion') }}</x-ui.button>
                    </form>
                @else
                    <x-ui.button :href="route('login')" variant="ghost" size="sm">{{ __('Connexion') }}</x-ui.button>
                @endauth
                <x-site.locale-switcher />
            </div>
        </nav>
    </div>
</header>
