@php use App\Enums\MessageChannel; @endphp

<x-layouts.admin :title="__('Conversation')" :heading="$conversation->user?->full_name ?? ('+'.$conversation->whatsapp_number)">
    <x-slot:actions>
        <form method="POST" action="{{ route('admin.messages.status', $conversation) }}">
            @csrf @method('PATCH')
            <x-ui.button type="submit" variant="outline" size="sm">
                {{ $conversation->status->value === 'open' ? __('Clore') : __('Rouvrir') }}
            </x-ui.button>
        </form>
        <x-ui.button :href="route('admin.messages.index')" variant="ghost" size="sm" icon="chevron-left">
            {{ __('Retour') }}
        </x-ui.button>
    </x-slot:actions>

    <div class="grid gap-5 lg:grid-cols-[1fr_18rem]">
        <div class="rounded-card border border-stone-200 bg-white">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-200 px-5 py-4">
                <div>
                    <p class="font-medium text-navy-900">{{ $conversation->subject }}</p>
                    @if ($conversation->property)
                        <a href="{{ route('admin.villas.compliance.show', $conversation->property) }}"
                           class="text-sm text-navy-500 hover:underline">{{ $conversation->property->name }}</a>
                    @endif
                </div>
                <x-ui.badge :variant="$conversation->status->value === 'open' ? 'success' : 'neutral'">
                    {{ $conversation->status->label() }}
                </x-ui.badge>
            </header>

            <div class="px-5 py-6">
                <x-message-thread :conversation="$conversation" perspective="admin" />
            </div>

            <form method="POST" action="{{ route('admin.messages.reply', $conversation) }}"
                  x-data="{ channel: '{{ $conversation->whatsapp_number ? MessageChannel::WhatsApp->value : MessageChannel::InApp->value }}' }"
                  class="border-t border-stone-200 p-4">
                @csrf
                <textarea name="body" rows="3" required minlength="2" maxlength="4000"
                          placeholder="{{ __('Votre réponse…') }}"
                          class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm text-navy-900 focus:border-navy-500">{{ old('body') }}</textarea>
                @error('body') <p class="mt-1 text-xs text-danger-700">{{ $message }}</p> @enderror

                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-1.5">
                        @foreach (MessageChannel::cases() as $case)
                            @php $disabled = $case === MessageChannel::WhatsApp && blank($conversation->whatsapp_number); @endphp
                            <label @class([
                                'inline-flex cursor-pointer items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm',
                                'opacity-40 cursor-not-allowed' => $disabled,
                            ])
                                   :class="channel === '{{ $case->value }}' ? 'border-navy-700 bg-navy-50 text-navy-900' : 'border-stone-300 text-navy-500'">
                                <input type="radio" name="channel" value="{{ $case->value }}" x-model="channel"
                                       class="sr-only" @disabled($disabled)>
                                <x-ui.icon :name="$case->icon()" class="size-4" />
                                {{ $case->label() }}
                            </label>
                        @endforeach
                    </div>

                    <x-ui.button type="submit" icon-after="arrow-right">{{ __('Répondre') }}</x-ui.button>
                </div>

                {{-- Deux mises en garde honnêtes plutôt qu'un envoi silencieusement inopérant. --}}
                <template x-if="channel === '{{ MessageChannel::WhatsApp->value }}'">
                    <div class="mt-3 flex flex-col gap-2">
                        @unless ($whatsappReady)
                            <p class="flex items-start gap-2 rounded-lg bg-warning-50 px-3 py-2 text-xs text-warning-700">
                                <x-ui.icon name="alert-triangle" class="mt-0.5 size-3.5 shrink-0" />
                                {{ __('La passerelle WhatsApp Cloud n\'est pas configurée : le message sera enregistré dans le fil et écrit dans les journaux, mais pas réellement envoyé.') }}
                            </p>
                        @endunless

                        @if ($whatsappReady && ! $conversation->whatsappWindowIsOpen())
                            <p class="flex items-start gap-2 rounded-lg bg-warning-50 px-3 py-2 text-xs text-warning-700">
                                <x-ui.icon name="alert-triangle" class="mt-0.5 size-3.5 shrink-0" />
                                {{ __('Plus de 24 h depuis le dernier message du client : Meta n\'accepte qu\'un modèle pré-approuvé, un message libre sera refusé.') }}
                            </p>
                        @endif
                    </div>
                </template>
            </form>
        </div>

        <aside class="flex flex-col gap-4">
            <div class="rounded-card border border-stone-200 bg-white p-5">
                <h2 class="text-sm font-semibold text-navy-900">{{ __('Client') }}</h2>
                @if ($conversation->user)
                    <dl class="mt-3 space-y-2 text-sm">
                        <div><dt class="text-xs text-navy-400">{{ __('Nom') }}</dt><dd class="text-navy-800">{{ $conversation->user->full_name }}</dd></div>
                        <div><dt class="text-xs text-navy-400">{{ __('E-mail') }}</dt>
                            <dd><a href="mailto:{{ $conversation->user->email }}" class="text-navy-800 hover:underline">{{ $conversation->user->email }}</a></dd></div>
                        @if ($conversation->user->phone)
                            <div><dt class="text-xs text-navy-400">{{ __('Téléphone') }}</dt>
                                <dd><a href="tel:{{ $conversation->user->phone }}" class="text-navy-800 hover:underline">{{ $conversation->user->phone }}</a></dd></div>
                        @endif
                    </dl>
                @else
                    <p class="mt-2 text-sm text-navy-500">
                        {{ __('Aucun compte rattaché — conversation ouverte depuis WhatsApp.') }}
                    </p>
                @endif

                @if ($conversation->whatsapp_number)
                    <x-whatsapp-button class="mt-4 w-full" size="sm" :number="$conversation->whatsapp_number">
                        {{ __('Ouvrir dans WhatsApp') }}
                    </x-whatsapp-button>
                @endif
            </div>

            @if ($conversation->booking)
                <div class="rounded-card border border-stone-200 bg-white p-5">
                    <h2 class="text-sm font-semibold text-navy-900">{{ __('Réservation') }}</h2>
                    <p class="mt-2 text-sm text-navy-600 tabular">{{ $conversation->booking->reference }}</p>
                </div>
            @endif
        </aside>
    </div>
</x-layouts.admin>
