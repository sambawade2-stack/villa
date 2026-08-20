@php
    use App\Enums\BookingStatus;
    $payment = $booking->payment;
    $awaitingPayment = $payment && ! $payment->status->isSettled() && $booking->isPending();
@endphp

<x-layouts.public noindex :title="$booking->reference.' — Petite Côte Villas'">
    <div class="container-page py-9">
        <x-ui.breadcrumb :items="[
            ['label' => __('Accueil'), 'url' => route('home')],
            ['label' => __('Mes réservations'), 'url' => route('bookings.index')],
            ['label' => $booking->reference],
        ]" />

        @if (session('status')) <x-ui.alert variant="success" class="mt-4">{{ session('status') }}</x-ui.alert> @endif
        @if (session('error')) <x-ui.alert variant="danger" class="mt-4">{{ session('error') }}</x-ui.alert> @endif

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl text-navy-900 lg:text-display">{{ $booking->property->name }}</h1>
            <x-ui.badge :variant="$booking->status->color()">{{ $booking->status->label() }}</x-ui.badge>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem]">
            <div class="flex flex-col gap-5">
                @if ($awaitingPayment)
                    <x-admin.panel :title="__('Règlement en attente')">
                        <div class="flex flex-col gap-4 p-5">
                            <p class="text-sm leading-relaxed text-navy-600">
                                {{ __('Réglez :total en indiquant la référence :ref. Votre réservation est confirmée dès que notre équipe constate la réception.', [
                                    'total' => $booking->total_amount->format(),
                                    'ref' => $booking->reference,
                                ]) }}
                            </p>

                            @if ($instructions)
                                <div class="whitespace-pre-line rounded-lg bg-stone-50 px-4 py-3 text-sm text-navy-700">{{ $instructions }}</div>
                            @endif

                            <x-whatsapp-button size="lg" class="self-start"
                                :message="__('Bonjour, j\'ai effectué le règlement de la réservation :ref.', ['ref' => $booking->reference])">
                                {{ __('Prévenir sur WhatsApp') }}
                            </x-whatsapp-button>
                        </div>
                    </x-admin.panel>
                @endif

                <x-admin.panel :title="__('Votre séjour')">
                    <dl class="grid gap-4 p-5 sm:grid-cols-2">
                        @foreach ([
                            [__('Arrivée'), $booking->checkin_date->translatedFormat('l d F Y').' — '.\Illuminate\Support\Carbon::parse($booking->property->checkin_time)->format('H:i')],
                            [__('Départ'), $booking->checkout_date->translatedFormat('l d F Y').' — '.\Illuminate\Support\Carbon::parse($booking->property->checkout_time)->format('H:i')],
                            [__('Voyageurs'), $booking->guests_count],
                            [__('Référence'), $booking->reference],
                        ] as [$label, $value])
                            <div>
                                <dt class="text-xs text-navy-400">{{ $label }}</dt>
                                <dd class="mt-0.5 text-sm font-medium text-navy-900">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if ($booking->isConfirmed())
                        <p class="border-t border-stone-200 bg-stone-50 px-5 py-3 text-sm text-navy-600">
                            <x-ui.icon name="map-pin" class="mr-1.5 inline size-4 text-navy-400" />
                            {{ __('L\'adresse exacte et les consignes d\'arrivée vous sont envoyées par message.') }}
                        </p>
                    @endif
                </x-admin.panel>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button :href="route('villas.show', [$booking->property->destination, $booking->property])" variant="outline">
                        {{ __('Revoir la villa') }}
                    </x-ui.button>

                    @if ($booking->status->canTransitionTo(BookingStatus::Cancelled))
                        <form method="POST" action="{{ route('bookings.cancel', $booking) }}"
                              onsubmit="return confirm('{{ __('Annuler cette réservation ?') }}')">
                            @csrf
                            <x-ui.button type="submit" variant="ghost" class="text-danger-700 hover:bg-danger-50">
                                {{ __('Annuler ma réservation') }}
                            </x-ui.button>
                        </form>
                    @endif

                    @if ($booking->acceptsReview())
                        <x-ui.badge variant="warning">{{ __('Vous pouvez déposer un avis') }}</x-ui.badge>
                    @endif
                </div>
            </div>

            <aside class="lg:sticky lg:top-24 lg:self-start">
                <x-admin.panel :title="__('Détail du prix')">
                    <x-booking-summary :booking="$booking" />
                </x-admin.panel>
            </aside>
        </div>
    </div>
</x-layouts.public>
