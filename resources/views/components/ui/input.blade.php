@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'icon' => null,
    'hint' => null,
    'error' => null,
])

@php
    $id = $attributes->get('id') ?? $name ?? 'in-'.Str::random(6);
    $error ??= $name ? ($errors?->first($name) ?: null) : null;
@endphp

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label for="{{ $id }}" class="field-label">
            {{ $label }}
        </label>
    @endif

    <div class="relative" @if ($type === 'password') x-data="{ visible: false }" @endif>
        @if ($icon)
            <x-ui.icon :name="$icon" class="size-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-navy-400 pointer-events-none" />
        @endif

        <input
            id="{{ $id }}"
            @if ($type === 'password')
                :type="visible ? 'text' : 'password'"
            @else
                type="{{ $type }}"
            @endif
            @if ($name) name="{{ $name }}" @endif
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->merge([
                'class' => 'w-full rounded-xl border bg-white py-2.5 text-sm font-medium text-navy-900 '
                    . 'placeholder:text-navy-300 transition-colors '
                    . ($icon ? 'pl-10 ' : 'pl-3.5 ')
                    . ($type === 'password' ? 'pr-10 ' : 'pr-3.5 ')
                    . ($error ? 'border-danger-500' : 'border-stone-300 hover:border-stone-400 focus:border-navy-500'),
            ]) }}
        >

        @if ($type === 'password')
            <button
                type="button"
                @click="visible = ! visible"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-navy-400 hover:text-navy-600"
                :aria-label="visible ? '{{ __('Masquer le mot de passe') }}' : '{{ __('Afficher le mot de passe') }}'"
            >
                <x-ui.icon x-show="! visible" name="eye" class="size-4.5" />
                <x-ui.icon x-show="visible" name="eye-off" class="size-4.5" x-cloak />
            </button>
        @endif
    </div>

    @if ($error)
        <p id="{{ $id }}-error" class="text-xs text-danger-700">{{ $error }}</p>
    @elseif ($hint)
        <p class="text-xs text-navy-400">{{ $hint }}</p>
    @endif
</div>
