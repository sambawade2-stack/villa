@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'iconAfter' => null,
])

@php
    /*
     * Le composant impose `inline-flex`, mais un appelant peut vouloir masquer
     * le bouton à certaines largeurs (« hidden sm:inline-flex »). Or l'ordre des
     * classes dans l'attribut ne départage rien : c'est l'ordre dans la feuille
     * de styles qui tranche, et `inline-flex` y gagnait — le bouton restait
     * visible sur mobile. On retire donc `inline-flex` de la base dès que
     * l'appelant fournit lui-même une utilitaire d'affichage.
     */
    $callerClasses = (string) $attributes->get('class', '');
    $callerSetsDisplay = (bool) preg_match(
        '/(^|\s)(\w+:)*(hidden|block|inline|inline-block|flex|inline-flex|grid|contents)(\s|$)/',
        $callerClasses
    );

    $base = ($callerSetsDisplay ? '' : 'inline-flex ')
          . 'items-center justify-center gap-2 font-medium rounded-lg '
          . 'transition-colors duration-150 disabled:opacity-50 disabled:pointer-events-none whitespace-nowrap';

    $variants = [
        'primary'       => 'bg-navy-800 text-white hover:bg-navy-700 active:bg-navy-900',
        'secondary'     => 'bg-stone-100 text-navy-900 hover:bg-stone-200',
        'outline'       => 'border border-navy-200 text-navy-800 bg-white hover:border-navy-400 hover:bg-navy-50',
        'outline-light' => 'border border-white/40 text-white bg-white/5 hover:bg-white/15 backdrop-blur-sm',
        'ghost'         => 'text-navy-600 hover:bg-stone-100 hover:text-navy-900',
        'amber'         => 'bg-amber-400 text-navy-900 hover:bg-amber-500',
        'danger'        => 'bg-danger-500 text-white hover:bg-danger-700',
    ];

    $sizes = [
        'sm' => 'text-sm px-3 py-1.5',
        'md' => 'text-sm px-4 py-2.5',
        'lg' => 'text-base px-6 py-3',
    ];

    $classes = implode(' ', [$base, $variants[$variant] ?? $variants['primary'], $sizes[$size] ?? $sizes['md']]);
    $iconSize = $size === 'lg' ? 'size-5' : 'size-4';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon) <x-ui.icon :name="$icon" :class="$iconSize" /> @endif
        {{ $slot }}
        @if ($iconAfter) <x-ui.icon :name="$iconAfter" :class="$iconSize" /> @endif
    </a>
@else
    <button {{ $attributes->merge(['class' => $classes, 'type' => 'button']) }}>
        @if ($icon) <x-ui.icon :name="$icon" :class="$iconSize" /> @endif
        {{ $slot }}
        @if ($iconAfter) <x-ui.icon :name="$iconAfter" :class="$iconSize" /> @endif
    </button>
@endif
