@props(['variant' => 'neutral', 'icon' => null])

@php
    $variants = [
        'neutral' => 'bg-stone-200 text-navy-700',
        'navy'    => 'bg-navy-900 text-stone-50',
        'gold'    => 'bg-amber-500/12 text-amber-600',
        'success' => 'bg-success-50 text-success-700',
        'warning' => 'bg-warning-50 text-warning-700',
        'danger'  => 'bg-danger-50 text-danger-700',
        'glass'   => 'bg-navy-950/55 text-white backdrop-blur-sm',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 '
        . 'text-xs font-medium whitespace-nowrap '
        . ($variants[$variant] ?? $variants['neutral']),
]) }}>
    @if ($icon) <x-ui.icon :name="$icon" class="size-3.5" /> @endif
    {{ $slot }}
</span>
