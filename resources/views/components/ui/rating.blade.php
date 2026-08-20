@props(['value' => null, 'count' => null, 'size' => 'md'])

@php
    $text = $size === 'lg' ? 'text-base' : 'text-sm';
    $star = $size === 'lg' ? 'size-4.5' : 'size-4';
@endphp

@if ($value === null)
    <span {{ $attributes->merge(['class' => "$text text-navy-300"]) }}>{{ __('Nouveau') }}</span>
@else
    <span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 $text"]) }}>
        <x-ui.icon name="star" class="{{ $star }} fill-amber-400 text-amber-400" />
        <span class="font-semibold text-navy-900 tabular">{{ number_format((float) $value, 1, ',', ' ') }}</span>
        @if ($count !== null)
            <span class="text-navy-400 tabular">({{ $count }})</span>
        @endif
    </span>
@endif
