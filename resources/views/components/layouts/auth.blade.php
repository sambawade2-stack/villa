<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <meta name="robots" content="noindex">
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col bg-stone-100">
    <div class="flex flex-1 items-center justify-center px-5 py-12">
        <div class="w-full max-w-md">
            <div class="flex justify-center">
                <x-site.logo />
            </div>

            <div class="mt-8 rounded-2xl border border-stone-200 bg-white p-7 shadow-card sm:p-8">
                <h1 class="text-title text-navy-900">{{ $heading }}</h1>
                @isset($intro)
                    <p class="mt-1.5 text-sm text-navy-500">{{ $intro }}</p>
                @endisset

                <div class="mt-6">{{ $slot }}</div>
            </div>

            @isset($footer)
                <p class="mt-6 text-center text-sm text-navy-500">{{ $footer }}</p>
            @endisset
        </div>
    </div>
    @livewireScriptConfig
</body>
</html>
