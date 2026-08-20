@props(['title' => null, 'heading' => null])

@php
    // Seules les entrées réellement fonctionnelles figurent ici. Les autres
    // rubriques de l'administration arrivent à l'étape 06 : un lien mort dans
    // une barre latérale coûte plus cher qu'une rubrique absente.
    $nav = [
        ['label' => __('Tableau de bord'), 'icon' => 'home', 'route' => 'admin.dashboard', 'url' => route('admin.dashboard')],
        ['label' => __('Villas'), 'icon' => 'key', 'route' => 'admin.villas.*', 'url' => route('admin.villas.index')],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? __('Administration') }} — Petite Côte Villas</title>
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-stone-50">
<div x-data="{ open: false }" class="flex min-h-full">

    {{-- ------------------------------------------------------ Barre latérale --}}
    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-navy-900 transition-transform lg:translate-x-0"
           :class="open ? 'translate-x-0' : '-translate-x-full'">
        <div class="flex h-[4.5rem] items-center px-5">
            <x-site.logo tone="light" />
        </div>

        <nav class="flex-1 space-y-1 px-3 py-4" aria-label="{{ __('Navigation de l\'administration') }}">
            @foreach ($nav as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ $item['url'] }}"
                   @if ($active) aria-current="page" @endif
                   @class([
                       'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors',
                       'bg-white/10 font-semibold text-white' => $active,
                       'text-white/65 hover:bg-white/5 hover:text-white' => ! $active,
                   ])>
                    <x-ui.icon :name="$item['icon']" class="size-5 shrink-0" />
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-3">
            <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-white/65 hover:bg-white/5 hover:text-white">
                <x-ui.icon name="globe" class="size-5" />{{ __('Voir le site') }}
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-white/65 hover:bg-white/5 hover:text-white">
                    <x-ui.icon name="x" class="size-5" />{{ __('Déconnexion') }}
                </button>
            </form>
        </div>
    </aside>

    <div x-show="open" x-cloak @click="open = false" class="fixed inset-0 z-30 bg-navy-950/40 lg:hidden"></div>

    {{-- ------------------------------------------------------------- Contenu --}}
    <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
        <header class="sticky top-0 z-20 flex h-[4.5rem] items-center gap-4 border-b border-stone-200 bg-white px-5 lg:px-8">
            <button type="button" @click="open = ! open" class="rounded-lg p-2 text-navy-700 hover:bg-stone-100 lg:hidden">
                <span class="sr-only">{{ __('Ouvrir le menu') }}</span>
                <x-ui.icon name="menu" class="size-6" />
            </button>

            <h1 class="min-w-0 truncate text-lg font-semibold text-navy-900">{{ $heading ?? $title }}</h1>

            <div class="ml-auto flex items-center gap-3">
                @isset($actions) {{ $actions }} @endisset
                <span class="hidden text-sm text-navy-500 sm:block">{{ auth()->user()->full_name }}</span>
            </div>
        </header>

        <main class="flex-1 p-5 lg:p-8">
            @if (session('status'))
                <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
            @endif
            @if (session('error'))
                <x-ui.alert variant="danger" class="mb-6">{{ session('error') }}</x-ui.alert>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>
@livewireScriptConfig
</body>
</html>
