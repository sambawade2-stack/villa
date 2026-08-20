<x-layouts.public :title="__('Mes messages — Petite Côte Villas')">
    <div class="border-b border-stone-200 bg-stone-50">
        <div class="container-page py-9">
            <x-ui.breadcrumb :items="[['label' => __('Accueil'), 'url' => route('home')], ['label' => __('Messages')]]" />
            <h1 class="mt-3 text-3xl text-navy-900 lg:text-display">{{ __('Mes messages') }}</h1>
            <p class="mt-1.5 text-navy-500">{{ __('Vos échanges avec l\'équipe Petite Côte Villas.') }}</p>
        </div>
    </div>

    <div class="container-page py-9">
        @if (session('status'))
            <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
        @endif

        @if ($conversations->isEmpty())
            <x-ui.empty-state icon="message-circle" :title="__('Aucune conversation')">
                {{ __('Écrivez-nous depuis une fiche villa ou par WhatsApp : vos échanges apparaîtront ici.') }}
                <x-slot:action>
                    <div class="flex flex-wrap justify-center gap-2">
                        <x-ui.button :href="route('villas.index')">{{ __('Parcourir les villas') }}</x-ui.button>
                        <x-whatsapp-button />
                    </div>
                </x-slot:action>
            </x-ui.empty-state>
        @else
            <div class="overflow-hidden rounded-card border border-stone-200 bg-white">
                <ul class="divide-y divide-stone-100">
                    @foreach ($conversations as $conversation)
                        <li>
                            <a href="{{ route('messages.show', $conversation) }}"
                               class="flex items-center gap-4 px-5 py-4 transition-colors hover:bg-stone-50">
                                <span @class([
                                    'flex size-10 shrink-0 items-center justify-center rounded-full',
                                    'bg-navy-800 text-white' => $conversation->customer_unread_count > 0,
                                    'bg-stone-100 text-navy-400' => $conversation->customer_unread_count === 0,
                                ])>
                                    <x-ui.icon name="message-circle" class="size-5" />
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium text-navy-900">{{ $conversation->subject }}</p>
                                    <p class="truncate text-sm text-navy-500">
                                        {{ Str::limit($conversation->latestMessage?->body, 90) }}
                                    </p>
                                </div>

                                <div class="shrink-0 text-right">
                                    <p class="text-xs text-navy-400 tabular">
                                        {{ $conversation->last_message_at?->diffForHumans(short: true) }}
                                    </p>
                                    @if ($conversation->customer_unread_count > 0)
                                        <span class="mt-1 inline-flex size-5 items-center justify-center rounded-full bg-amber-400 text-[0.65rem] font-bold text-navy-900 tabular">
                                            {{ $conversation->customer_unread_count }}
                                        </span>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="mt-8">{{ $conversations->links() }}</div>
        @endif
    </div>
</x-layouts.public>
