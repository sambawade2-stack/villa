@props(['conversation', 'perspective' => 'customer'])

{{-- $perspective : « customer » ou « admin ». Détermine de quel côté s'affiche
     chaque bulle — un message de l'admin est à droite pour l'admin, à gauche
     pour le client. --}}

<div class="flex flex-col gap-4">
    @forelse ($conversation->messages as $message)
        @php
            $fromAdmin = $message->sender?->isAdmin() ?? false;
            $mine = $perspective === 'admin' ? $fromAdmin : ! $fromAdmin;
        @endphp

        <div @class(['flex', 'justify-end' => $mine])>
            <div @class(['max-w-[80%] sm:max-w-[70%]'])>
                <div @class([
                    'rounded-2xl px-4 py-2.5 text-sm leading-relaxed whitespace-pre-line',
                    'bg-navy-800 text-white rounded-br-md' => $mine,
                    'bg-stone-100 text-navy-800 rounded-bl-md' => ! $mine,
                ])>{{ $message->body }}</div>

                <p @class(['mt-1 flex items-center gap-1.5 text-[0.7rem] text-navy-400', 'justify-end' => $mine])>
                    @if ($message->channel === \App\Enums\MessageChannel::WhatsApp)
                        <span class="inline-flex items-center gap-1 text-[#1FA855]">
                            <x-ui.icon name="message-circle" class="size-3" />WhatsApp
                        </span>
                        <span aria-hidden="true">·</span>
                    @endif

                    <span class="tabular">{{ $message->created_at->format('d/m/Y H:i') }}</span>

                    @if ($message->hasFailed())
                        <span aria-hidden="true">·</span>
                        <span class="inline-flex items-center gap-1 text-danger-700" title="{{ $message->failure_reason }}">
                            <x-ui.icon name="alert-triangle" class="size-3" />{{ __('non remis') }}
                        </span>
                    @elseif ($message->delivered_at)
                        <span aria-hidden="true">·</span>
                        <span class="inline-flex items-center gap-1"><x-ui.icon name="check" class="size-3" />{{ __('remis') }}</span>
                    @endif
                </p>
            </div>
        </div>
    @empty
        <p class="py-8 text-center text-sm text-navy-400">{{ __('Aucun message dans ce fil.') }}</p>
    @endforelse
</div>
