@props(['tone' => 'navy'])

@php
    $word = $tone === 'light' ? 'text-white' : 'text-navy-900';
@endphp

<a href="{{ route('home') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}
   aria-label="Petite Côte Villas — {{ __('accueil') }}">

    {{-- Soleil de la Petite Côte : disque plein et rayons, en ambre. --}}
    <svg viewBox="0 0 24 24" class="size-6 shrink-0 text-amber-400" role="img" aria-hidden="true" focusable="false">
        <circle cx="12" cy="12" r="4.2" fill="currentColor" />
        <g stroke="currentColor" stroke-width="1.7" stroke-linecap="round">
            <path d="M12 1.9v2.4M12 19.7v2.4M22.1 12h-2.4M4.3 12H1.9" />
            <path d="M19.14 4.86 17.44 6.56M6.56 17.44l-1.7 1.7M19.14 19.14l-1.7-1.7M6.56 6.56l-1.7-1.7" />
        </g>
    </svg>

    <span class="font-sans text-[1.0625rem] font-semibold tracking-[-0.01em] {{ $word }}">
        Petite Côte Villas
    </span>
</a>
