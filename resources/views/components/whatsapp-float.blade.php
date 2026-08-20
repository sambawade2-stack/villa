@php
    $url = \App\Services\WhatsApp\WhatsAppLink::to(
        __("Bonjour, je vous écris depuis votre site à propos d'une villa sur la Petite Côte.")
    );
@endphp

@if ($url)
    {{-- Bouton flottant : sur la Petite Côte, WhatsApp est le canal par défaut. --}}
    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
       class="fixed bottom-5 right-5 z-30 flex items-center gap-2.5 rounded-full bg-[#25D366] px-4 py-3
              text-sm font-semibold text-white shadow-lifted transition hover:scale-105 hover:bg-[#1FB855]">
        <svg viewBox="0 0 24 24" class="size-5 fill-current" aria-hidden="true" focusable="false">
            <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2m0 1.67c2.2 0 4.27.86 5.83 2.42a8.2 8.2 0 0 1 2.41 5.82c0 4.54-3.7 8.24-8.25 8.24a8.24 8.24 0 0 1-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.19 8.19 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.26-8.24m-4.55 4.4c-.2 0-.53.08-.81.38-.28.3-1.06 1.04-1.06 2.53s1.09 2.94 1.24 3.14c.15.2 2.13 3.25 5.17 4.44 2.53.99 3.05.79 3.6.74.55-.05 1.77-.72 2.02-1.42.25-.7.25-1.3.17-1.42-.07-.13-.27-.2-.57-.35-.3-.15-1.77-.87-2.04-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48a9 9 0 0 1-1.66-2.06c-.17-.3-.02-.46.13-.61.14-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.38-.02-.53-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51z"/>
        </svg>
        <span class="hidden sm:inline">{{ __('Écrire sur WhatsApp') }}</span>
        <span class="sr-only sm:hidden">{{ __('Écrire sur WhatsApp') }}</span>
    </a>
@endif
