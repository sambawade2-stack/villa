<x-layouts.public noindex :title="__('Finaliser la réservation — Petite Côte Villas')">
    <div class="container-page py-9">
        <x-ui.breadcrumb :items="[
            ['label' => __('Accueil'), 'url' => route('home')],
            ['label' => __('Mes réservations'), 'url' => route('bookings.index')],
            ['label' => __('Paiement')],
        ]" />

        <h1 class="mt-3 text-2xl text-navy-900 lg:text-display">{{ __('Finaliser votre réservation') }}</h1>

        {{-- Compte à rebours : les dates ne sont tenues qu'un temps, et le
             visiteur doit le savoir avant de partir chercher son téléphone. --}}
        <div x-data="{
                left: {{ max(0, now()->diffInSeconds($booking->hold_expires_at, false)) }},
                get label() {
                    const m = Math.floor(this.left / 60), s = this.left % 60;
                    return m + ' min ' + String(s).padStart(2, '0') + ' s';
                },
             }"
             x-init="setInterval(() => { if (left > 0) left-- }, 1000)"
             class="mt-4 flex items-center gap-2.5 rounded-lg bg-warning-50 px-4 py-3 text-sm text-warning-700">
            <x-ui.icon name="calendar" class="size-4 shrink-0" />
            <template x-if="left > 0">
                <span>{{ __('Vos dates sont tenues encore') }} <strong x-text="label" class="tabular"></strong>.</span>
            </template>
            <template x-if="left <= 0">
                <span>{{ __('Le délai est écoulé — rechargez la page.') }}</span>
            </template>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem]">
            <div class="flex flex-col gap-5">
                @if (session('error'))
                    <x-ui.alert variant="danger">{{ session('error') }}</x-ui.alert>
                @endif

                <x-admin.panel :title="__('Votre séjour')">
                    <dl class="grid gap-4 p-5 sm:grid-cols-2">
                        @foreach ([
                            [__('Villa'), $booking->property->name],
                            [__('Destination'), (string) $booking->property->destination?->name],
                            [__('Arrivée'), $booking->checkin_date->translatedFormat('l d F Y')],
                            [__('Départ'), $booking->checkout_date->translatedFormat('l d F Y')],
                            [__('Voyageurs'), $booking->guests_count],
                            [__('Référence'), $booking->reference],
                        ] as [$label, $value])
                            <div>
                                <dt class="text-xs text-navy-400">{{ $label }}</dt>
                                <dd class="mt-0.5 text-sm font-medium text-navy-900">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-admin.panel>

                <form method="POST" action="{{ route('bookings.pay', $booking) }}">
                    @csrf
                    <x-admin.panel :title="__('Mode de règlement')">
                        <div class="flex flex-col gap-3 p-5">
                            @foreach ($gateways as $gateway)
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-stone-300 p-4 transition-colors hover:border-navy-400 has-[:checked]:border-navy-700 has-[:checked]:bg-navy-50">
                                    <input type="radio" name="gateway" value="{{ $gateway->name() }}"
                                           @checked($loop->first) required class="mt-1 size-4 text-navy-800">
                                    <span>
                                        <span class="block text-sm font-medium text-navy-900">{{ $gateway->label() }}</span>
                                        @if ($gateway->isOffline())
                                            <span class="mt-0.5 block text-xs text-navy-500">
                                                {{ __('Vous recevez les coordonnées de règlement. Votre réservation est confirmée dès que notre équipe constate la réception.') }}
                                            </span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach

                            {{-- Honnêteté sur l'état réel du système. --}}
                            <p class="flex items-start gap-2 text-xs leading-relaxed text-navy-400">
                                <x-ui.icon name="info" class="mt-0.5 size-3.5 shrink-0" />
                                {{ __('Le paiement par carte et par mobile money en ligne arrive prochainement. Pour l\'instant, le règlement se fait hors ligne et notre équipe le constate.') }}
                            </p>
                        </div>
                    </x-admin.panel>

                    <x-ui.button type="submit" size="lg" class="mt-5 w-full sm:w-auto sm:px-10">
                        {{ __('Valider ma demande') }}
                    </x-ui.button>
                </form>
            </div>

            <aside class="lg:sticky lg:top-24 lg:self-start">
                <x-admin.panel :title="__('Détail du prix')">
                    <x-booking-summary :booking="$booking" />
                </x-admin.panel>
            </aside>
        </div>
    </div>
</x-layouts.public>
