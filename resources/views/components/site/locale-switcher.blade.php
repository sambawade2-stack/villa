@props(['tone' => 'navy'])

@php
    $current = app()->getLocale();
    $locales = config('app.available_locales', ['fr' => 'Français', 'en' => 'English']);
    $flags = ['fr' => '🇫🇷', 'en' => '🇬🇧'];
@endphp

<div x-data="{ open: false }" @click.outside="open = false" {{ $attributes->merge(['class' => 'relative']) }}>
    <button type="button" @click="open = ! open" :aria-expanded="open"
            @class([
                'inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm transition-colors',
                'text-white/85 hover:bg-white/10' => $tone === 'light',
                'text-navy-600 hover:bg-stone-100' => $tone !== 'light',
            ])>
        <span class="sr-only">{{ __('Changer de langue') }}</span>
        <span aria-hidden="true" class="text-base leading-none">{{ $flags[$current] ?? '🌐' }}</span>
        <x-ui.icon name="chevron-down" class="size-3.5" />
    </button>

    <div x-show="open" x-cloak x-transition.opacity
         class="absolute right-0 z-50 mt-1.5 w-40 overflow-hidden rounded-xl border border-stone-200 bg-white py-1 shadow-lifted">
        @foreach ($locales as $code => $label)
            <a href="{{ route('locale.switch', $code) }}" hreflang="{{ $code }}"
               @class([
                   'flex items-center gap-2.5 px-3 py-2 text-sm',
                   'bg-stone-50 font-semibold text-navy-900' => $code === $current,
                   'text-navy-600 hover:bg-stone-50' => $code !== $current,
               ])>
                <span aria-hidden="true">{{ $flags[$code] ?? '🌐' }}</span>
                {{ $label }}
                @if ($code === $current)
                    <x-ui.icon name="check" class="ml-auto size-4 text-navy-500" />
                @endif
            </a>
        @endforeach
    </div>
</div>
