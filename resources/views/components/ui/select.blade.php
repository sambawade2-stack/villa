@props([
    'label' => null,
    'name' => null,
    'icon' => null,
    'error' => null,
])

@php
    $id = $attributes->get('id') ?? $name ?? 'sel-'.Str::random(6);
    $error ??= $name ? ($errors?->first($name) ?: null) : null;
@endphp

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label for="{{ $id }}" class="field-label">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        @if ($icon)
            <x-ui.icon :name="$icon" class="size-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-navy-400 pointer-events-none" />
        @endif

        <select
            id="{{ $id }}"
            @if ($name) name="{{ $name }}" @endif
            {{ $attributes->merge([
                'class' => 'w-full appearance-none rounded-xl border bg-white py-2.5 pr-10 text-sm font-medium text-navy-900 '
                    . 'transition-colors '
                    . ($icon ? 'pl-10 ' : 'pl-3.5 ')
                    . ($error ? 'border-danger-500' : 'border-stone-300 hover:border-stone-400 focus:border-navy-500'),
            ]) }}
        >
            {{ $slot }}
        </select>

        <x-ui.icon name="chevron-down" class="size-4 absolute right-3.5 top-1/2 -translate-y-1/2 text-navy-400 pointer-events-none" />
    </div>

    @if ($error)
        <p class="text-xs text-danger-700">{{ $error }}</p>
    @endif
</div>
