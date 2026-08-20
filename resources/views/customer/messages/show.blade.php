<x-layouts.public :title="$conversation->subject.' — Petite Côte Villas'">
    <div class="container-page py-9">
        <x-ui.breadcrumb :items="[
            ['label' => __('Accueil'), 'url' => route('home')],
            ['label' => __('Messages'), 'url' => route('messages.index')],
            ['label' => Str::limit($conversation->subject, 40)],
        ]" />

        <div class="mt-5 grid gap-6 lg:grid-cols-[1fr_18rem]">
            <div class="rounded-card border border-stone-200 bg-white">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-200 px-5 py-4">
                    <div>
                        <h1 class="text-lg font-semibold text-navy-900">{{ $conversation->subject }}</h1>
                        @if ($conversation->property)
                            <a href="{{ route('villas.show', $conversation->property) }}"
                               class="text-sm text-navy-500 hover:text-navy-900 hover:underline">
                                {{ $conversation->property->name }}
                            </a>
                        @endif
                    </div>
                    <x-ui.badge :variant="$conversation->status->value === 'open' ? 'success' : 'neutral'">
                        {{ $conversation->status->label() }}
                    </x-ui.badge>
                </header>

                <div class="px-5 py-6">
                    <x-message-thread :conversation="$conversation" perspective="customer" />
                </div>

                <form method="POST" action="{{ route('messages.store', $conversation) }}"
                      class="border-t border-stone-200 p-4">
                    @csrf
                    <label for="body" class="sr-only">{{ __('Votre message') }}</label>
                    <textarea id="body" name="body" rows="3" required minlength="2" maxlength="4000"
                              placeholder="{{ __('Écrivez votre message…') }}"
                              class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm text-navy-900 focus:border-navy-500">{{ old('body') }}</textarea>
                    @error('body') <p class="mt-1 text-xs text-danger-700">{{ $message }}</p> @enderror

                    <div class="mt-3 flex items-center justify-between gap-3">
                        <p class="text-xs text-navy-400">{{ __('Réponse sous 2 heures ouvrées.') }}</p>
                        <x-ui.button type="submit" icon-after="arrow-right">{{ __('Envoyer') }}</x-ui.button>
                    </div>
                </form>
            </div>

            <aside class="flex flex-col gap-4">
                <div class="rounded-card border border-stone-200 bg-white p-5">
                    <h2 class="text-sm font-semibold text-navy-900">{{ __('Préférez WhatsApp ?') }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-navy-500">
                        {{ __('Vos messages WhatsApp arrivent dans ce même fil : l\'équipe vous répond au même endroit.') }}
                    </p>
                    <x-whatsapp-button class="mt-3 w-full"
                        :message="__('Bonjour, je vous écris au sujet de : :sujet', ['sujet' => $conversation->subject])" />
                </div>

                @if ($conversation->property)
                    <div class="rounded-card border border-stone-200 bg-white p-5">
                        <h2 class="text-sm font-semibold text-navy-900">{{ __('Villa concernée') }}</h2>
                        <a href="{{ route('villas.show', $conversation->property) }}"
                           class="mt-2 block text-sm text-navy-600 hover:underline">
                            {{ $conversation->property->name }}
                        </a>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</x-layouts.public>
