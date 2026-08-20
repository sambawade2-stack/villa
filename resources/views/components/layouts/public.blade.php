@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'ogType' => 'website',
    'ogImage' => null,
    'transparentNav' => false,
    'noindex' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?: config('app.name') }}</title>

    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif

    @php
        /*
         | Canonique et alternatives de langue.
         |
         | La canonique pointe sur la page dans la langue servie, paramètre
         | `lang` compris : sans lui, les deux versions se déclareraient
         | identiques et une seule serait indexée. Les paramètres de recherche
         | (dates, filtres) sont en revanche écartés — ils produisent des
         | variantes sans valeur propre pour un moteur.
         */
        $localeParam = app()->getLocale() === config('app.fallback_locale') ? [] : ['lang' => app()->getLocale()];
        $canonicalUrl = $canonical ?: url()->current().($localeParam ? '?lang='.app()->getLocale() : '');
        $baseUrl = $canonical ? strtok($canonical, '?') : url()->current();
    @endphp

    @if ($noindex)
        {{-- Page privée : ni indexée, ni suivie. --}}
        <meta name="robots" content="noindex, nofollow">
    @else
        <link rel="canonical" href="{{ $canonicalUrl }}">
    @endif

    {{-- Open Graph : l'aperçu partagé compte autant que la page pour un site touristique. --}}
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $title ?: config('app.name') }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:locale" content="{{ app()->getLocale() === 'fr' ? 'fr_FR' : 'en_GB' }}">
    @if ($description)
        <meta property="og:description" content="{{ $description }}">
    @endif
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif

    {{-- hreflang doit désigner la page équivalente, jamais l'action de bascule. --}}
    @foreach (array_keys(config('app.available_locales', [])) as $code)
        <link rel="alternate" hreflang="{{ $code }}"
              href="{{ $baseUrl.($code === config('app.fallback_locale') ? '' : '?lang='.$code) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $baseUrl }}">

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body class="flex min-h-full flex-col">
    <a href="#contenu"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-full focus:bg-navy-900 focus:px-5 focus:py-2.5 focus:text-sm focus:text-white">
        {{ __('Aller au contenu') }}
    </a>

    <x-site.navbar :transparent="$transparentNav" />

    <main id="contenu" class="flex-1">
        {{ $slot }}
    </main>

    <x-site.footer />

    <x-whatsapp-float />

    @livewireScriptConfig
    @stack('scripts')
</body>
</html>
