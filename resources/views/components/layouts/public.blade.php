<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>

    @isset($description)
        <meta name="description" content="{{ $description }}">
    @endisset

    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">

    {{-- Open Graph : l'aperçu partagé compte autant que la page pour un site touristique. --}}
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $title ?? config('app.name') }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    <meta property="og:locale" content="{{ app()->getLocale() === 'fr' ? 'fr_FR' : 'en_GB' }}">
    @isset($description)
        <meta property="og:description" content="{{ $description }}">
    @endisset
    @isset($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:card" content="summary_large_image">
    @endisset

    @foreach (config('app.available_locales', []) as $code => $label)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ route('locale.switch', $code) }}">
    @endforeach

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body class="flex min-h-full flex-col">
    <a href="#contenu"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-full focus:bg-navy-900 focus:px-5 focus:py-2.5 focus:text-sm focus:text-white">
        {{ __('Aller au contenu') }}
    </a>

    <x-site.navbar :transparent="$transparentNav ?? false" />

    <main id="contenu" class="flex-1">
        {{ $slot }}
    </main>

    <x-site.footer />

    <x-whatsapp-float />

    @livewireScriptConfig
    @stack('scripts')
</body>
</html>
