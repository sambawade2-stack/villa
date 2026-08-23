@php
    $complianceVariant = match ($compliance['status']) {
        'verified' => 'success',
        'blocked' => 'danger',
        default => 'warning',
    };
    $complianceLabel = match ($compliance['status']) {
        'verified' => __('Dossier vérifié'),
        'blocked' => __('Pièce à corriger'),
        default => __('Dossier incomplet'),
    };
@endphp

<x-layouts.public noindex :title="$property->name.' — Petite Côte Villas'">
    <div class="container-page py-9">
        <x-ui.breadcrumb :items="[
            ['label' => __('Accueil'), 'url' => route('home')],
            ['label' => __('Mon espace propriétaire'), 'url' => route('owner.dashboard')],
            ['label' => $property->name],
        ]" />

        @if (session('status')) <x-ui.alert variant="success" class="mt-4">{{ session('status') }}</x-ui.alert> @endif
        @if (session('error')) <x-ui.alert variant="danger" class="mt-4">{{ session('error') }}</x-ui.alert> @endif

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl text-navy-900 lg:text-display">{{ $property->name }}</h1>
            <x-ui.badge :variant="$property->status->color()">{{ $property->status->label() }}</x-ui.badge>
        </div>
        <p class="mt-1 text-navy-400">{{ $property->destination?->name }}</p>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <article class="rounded-card border border-stone-200 bg-white p-5">
                <p class="text-sm text-navy-500">{{ __('Conformité') }}</p>
                <p class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-navy-900 tabular">{{ $compliance['satisfied'] }}/{{ $compliance['total'] }}</span>
                    <x-ui.badge :variant="$complianceVariant">{{ $complianceLabel }}</x-ui.badge>
                </p>
                <p class="mt-2 text-xs leading-relaxed text-navy-400">
                    {{ __('Le badge « villa vérifiée » ne s\'active que lorsque toutes les pièces requises sont contrôlées par l\'administration.') }}
                </p>
            </article>

            <article class="rounded-card border border-stone-200 bg-white p-5">
                <p class="text-sm text-navy-500">{{ __('Villa vérifiée') }}</p>
                <p class="mt-2">
                    <x-ui.badge :variant="$property->is_verified ? 'success' : 'warning'">
                        {{ $property->is_verified ? __('Oui, affichée sur la fiche publique') : __('Pas encore') }}
                    </x-ui.badge>
                </p>
            </article>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem]">
            <div class="flex flex-col gap-5">
                <x-admin.panel :title="__('Réservations')">
                    @if ($bookings->isEmpty())
                        <p class="p-5 text-center text-sm text-navy-400">{{ __('Aucune réservation pour l\'instant.') }}</p>
                    @else
                        <x-admin.table :headers="[__('Dates'), __('Voyageurs'), __('Statut'), __('Vous revient')]">
                            @foreach ($bookings as $booking)
                                <tr>
                                    <td class="px-4 py-3 text-navy-900 tabular">
                                        {{ $booking->checkin_date->format('d/m/Y') }} → {{ $booking->checkout_date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-navy-600">{{ $booking->guests_count }}</td>
                                    <td class="px-4 py-3"><x-ui.badge :variant="$booking->status->color()">{{ $booking->status->label() }}</x-ui.badge></td>
                                    <td class="px-4 py-3 text-navy-900 tabular">
                                        {{ $booking->owner_payout_amount?->format() ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </x-admin.table>
                        <div class="border-t border-stone-200 p-4">
                            {{ $bookings->onEachSide(1)->links() }}
                        </div>
                    @endif
                </x-admin.panel>
            </div>

            <aside class="flex flex-col gap-5 lg:sticky lg:top-24 lg:self-start">
                <x-admin.panel :title="__('Bloquer des dates')"
                               :subtitle="__('Pour votre usage personnel. Un blocage ne peut pas chevaucher une réservation en cours.')">
                    <form method="POST" action="{{ route('owner.villas.blocks.store', $property) }}"
                          class="flex flex-col gap-3 p-5">
                        @csrf
                        <x-ui.input type="date" name="starts_on" :label="__('Du')" required :value="old('starts_on')" />
                        <x-ui.input type="date" name="ends_on" :label="__('Au')" required :value="old('ends_on')" />
                        <x-ui.input name="note" :label="__('Note (facultatif)')" :value="old('note')"
                                    placeholder="{{ __('Séjour familial') }}" />
                        <x-ui.button type="submit" size="sm" icon="plus">{{ __('Bloquer cette période') }}</x-ui.button>
                    </form>

                    @if ($blocks->isNotEmpty())
                        <ul class="flex flex-col divide-y divide-stone-100 border-t border-stone-200">
                            @foreach ($blocks as $block)
                                <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                                    <div>
                                        <p class="text-navy-900 tabular">
                                            {{ $block->starts_on->format('d/m/Y') }} → {{ $block->ends_on->format('d/m/Y') }}
                                        </p>
                                        <p class="text-xs text-navy-400">{{ $block->reason->label() }}{{ $block->note ? ' — '.$block->note : '' }}</p>
                                    </div>
                                    @if ($block->reason->value === 'owner_use')
                                        <form method="POST" action="{{ route('owner.villas.blocks.destroy', [$property, $block]) }}"
                                              onsubmit="return confirm('{{ __('Libérer ces dates ?') }}')">
                                            @csrf @method('DELETE')
                                            <x-ui.button type="submit" variant="ghost" size="sm" class="text-danger-700 hover:bg-danger-50">
                                                {{ __('Libérer') }}
                                            </x-ui.button>
                                        </form>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-admin.panel>
            </aside>
        </div>
    </div>
</x-layouts.public>
