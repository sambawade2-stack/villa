@props(['message' => null, 'number' => null, 'variant' => 'outline', 'size' => 'md', 'icon' => 'message-circle'])

@php
    $url = \App\Services\WhatsApp\WhatsAppLink::to($message, $number);
@endphp

@if ($url)
    <x-ui.button :href="$url" :variant="$variant" :size="$size" :icon="$icon"
                 target="_blank" rel="noopener noreferrer" {{ $attributes }}>
        {{ $slot->isEmpty() ? __('WhatsApp') : $slot }}
    </x-ui.button>
@endif
