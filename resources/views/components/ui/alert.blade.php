@props(['variant' => 'info', 'title' => null])

@php
    $config = [
        'info'    => ['bg-navy-50 text-navy-800 border-navy-200', 'info'],
        'success' => ['bg-success-50 text-success-700 border-success-500/25', 'circle-check'],
        'warning' => ['bg-warning-50 text-warning-700 border-warning-500/25', 'alert-triangle'],
        'danger'  => ['bg-danger-50 text-danger-700 border-danger-500/25', 'circle-x'],
    ];

    [$classes, $icon] = $config[$variant] ?? $config['info'];
@endphp

<div role="alert" {{ $attributes->merge(['class' => "flex gap-3 rounded-xl border px-4 py-3 text-sm {$classes}"]) }}>
    <x-ui.icon :name="$icon" class="size-5 shrink-0 mt-0.5" />
    <div class="flex flex-col gap-1">
        @if ($title) <p class="font-semibold">{{ $title }}</p> @endif
        <div>{{ $slot }}</div>
    </div>
</div>
