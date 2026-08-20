@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end justify-between gap-4']) }}>
    <div>
        <h2 class="text-2xl text-navy-900 lg:text-display">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-1.5 text-sm text-navy-500">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($action)
        <div class="shrink-0">{{ $action }}</div>
    @endisset
</div>
