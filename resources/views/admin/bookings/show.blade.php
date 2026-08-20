<x-layouts.admin :title="$booking->reference" :heading="$booking->reference">
    <x-slot:actions>
        <x-ui.badge :variant="$booking->status->color()">{{ $booking->status->label() }}</x-ui.badge>
        <x-ui.button :href="route('admin.bookings.index')" variant="ghost" size="sm" icon="chevron-left">{{ __('Retour') }}</x-ui.button>
    </x-slot:actions>

    <div class="grid gap-5 lg:grid-cols-[1fr_20rem]">
        <div class="flex flex-col gap-5">
            <x-admin.panel :title="__('Séjour')">
                <dl class="grid gap-4 p-5 sm:grid-cols-3">
                    @foreach ([
                        [__('Arrivée'), $booking->checkin_date->translatedFormat('d F Y')],
                        [__('Départ'), $booking->checkout_date->translatedFormat('d F Y')],
                        [__('Nuits'), $booking->nights],
                        [__('Voyageurs'), $booking->guests_count],
                        [__('Villa'), $booking->property?->name],
                        [__('Destination'), (string) $booking->property?->destination?->name],
                    ] as [$label, $value])
                        <div>
                            <dt class="text-xs text-navy-400">{{ $label }}</dt>
                            <dd class="mt-0.5 text-sm font-medium text-navy-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-admin.panel>

            <x-admin.panel :title="__('Décomposition du prix')"
                           :subtitle="__('Figée à la réservation : un changement de tarif ne la réécrit pas.')">
                <dl class="divide-y divide-stone-100 text-sm">
                    @foreach (array_filter([
                        [__('Sous-total des nuits'), $booking->nightly_subtotal],
                        [__('Frais de ménage'), $booking->cleaning_fee],
                        [__('Frais de service'), $booking->service_fee],
                        [__('Remise'), $booking->discount_total],
                    ], fn ($row) => ! $row[1]->isZero()) as [$label, $amount])
                        <div class="flex justify-between gap-4 px-5 py-3">
                            <dt class="text-navy-500">{{ $label }}</dt>
                            <dd class="text-navy-900 tabular">{{ $amount->format() }}</dd>
                        </div>
                    @endforeach
                    <div class="flex justify-between gap-4 bg-stone-50 px-5 py-3 font-semibold">
                        <dt class="text-navy-900">{{ __('Total payé par le client') }}</dt>
                        <dd class="text-navy-900 tabular">{{ $booking->total_amount->format() }}</dd>
                    </div>
                    @if ($booking->commission)
                        <div class="flex justify-between gap-4 px-5 py-3">
                            <dt class="text-navy-500">{{ __('Commission plateforme (:rate %)', ['rate' => $booking->commission->rate]) }}</dt>
                            <dd class="text-navy-900 tabular">{{ $booking->commission->commission_amount->format() }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 px-5 py-3">
                            <dt class="text-navy-500">{{ __('À reverser au propriétaire') }}</dt>
                            <dd class="text-navy-900 tabular">{{ $booking->commission->owner_payout_amount->format() }}</dd>
                        </div>
                    @endif
                </dl>
            </x-admin.panel>

            @if ($booking->payments->isNotEmpty())
                <x-admin.panel :title="__('Paiements')">
                    <x-admin.table :headers="[__('Passerelle'), __('Montant'), __('Statut'), __('Référence'), __('Payé le')]">
                        @foreach ($booking->payments as $payment)
                            <tr>
                                <td class="px-4 py-3 text-navy-600">{{ $payment->gateway }}</td>
                                <td class="px-4 py-3 text-navy-900 tabular">{{ $payment->amount->format() }}</td>
                                <td class="px-4 py-3"><x-ui.badge :variant="$payment->status->color()">{{ $payment->status->label() }}</x-ui.badge></td>
                                <td class="px-4 py-3 text-navy-500 tabular">{{ $payment->provider_reference ?? '—' }}</td>
                                <td class="px-4 py-3 text-navy-500 tabular">{{ $payment->paid_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </x-admin.table>
                </x-admin.panel>
            @endif
        </div>

        <aside class="flex flex-col gap-5">
            <x-admin.panel :title="__('Client')">
                <div class="flex flex-col gap-2 p-5 text-sm">
                    <p class="font-medium text-navy-900">{{ $booking->user?->full_name }}</p>
                    <a href="mailto:{{ $booking->user?->email }}" class="text-navy-600 hover:underline">{{ $booking->user?->email }}</a>
                    @if ($booking->user?->phone)
                        <a href="tel:{{ $booking->user->phone }}" class="text-navy-600 hover:underline tabular">{{ $booking->user->phone }}</a>
                    @endif
                    @if ($booking->user?->whatsapp)
                        <x-whatsapp-button class="mt-2" size="sm" :number="$booking->user->whatsapp"
                            :message="__('Bonjour, au sujet de votre réservation :ref', ['ref' => $booking->reference])">
                            {{ __('WhatsApp') }}
                        </x-whatsapp-button>
                    @endif
                </div>
            </x-admin.panel>

            @if ($booking->property?->owner)
                <x-admin.panel :title="__('Propriétaire')">
                    <div class="flex flex-col gap-2 p-5 text-sm">
                        <a href="{{ route('admin.owners.show', $booking->property->owner) }}"
                           class="font-medium text-navy-900 hover:underline">{{ $booking->property->owner->full_name }}</a>
                        <span class="text-navy-600 tabular">{{ $booking->property->owner->phone }}</span>
                    </div>
                </x-admin.panel>
            @endif

            @if ($booking->guest_note || $booking->admin_note || $booking->cancellation_reason)
                <x-admin.panel :title="__('Notes')">
                    <div class="flex flex-col gap-3 p-5 text-sm">
                        @if ($booking->guest_note)
                            <div><p class="text-xs text-navy-400">{{ __('Message du client') }}</p>
                                <p class="mt-1 text-navy-700">{{ $booking->guest_note }}</p></div>
                        @endif
                        @if ($booking->cancellation_reason)
                            <div><p class="text-xs text-navy-400">{{ __('Motif d\'annulation') }}</p>
                                <p class="mt-1 text-navy-700">{{ $booking->cancellation_reason }}</p></div>
                        @endif
                    </div>
                </x-admin.panel>
            @endif
        </aside>
    </div>
</x-layouts.admin>
