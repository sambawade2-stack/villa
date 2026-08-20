@php use App\Enums\BookingStatus; @endphp

<x-layouts.admin :title="__('Réservations')" :heading="__('Réservations')">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            @if ($status) <input type="hidden" name="status" value="{{ $status }}"> @endif
            <x-ui.input name="q" icon="search" :value="request('q')"
                        placeholder="{{ __('Référence, client, villa…') }}" class="w-80" />
            <x-ui.button type="submit" variant="outline">{{ __('Rechercher') }}</x-ui.button>
        </form>

        <x-admin.filter-tabs route="admin.bookings.index" :current="$status" :counts="$counts"
                             :all-label="__('Toutes')"
                             :options="collect(BookingStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" />
    </div>

    <x-admin.panel class="mt-5">
        <x-admin.table :headers="[__('Référence'), __('Client'), __('Villa'), __('Dates'), __('Montant'), __('Paiement'), __('Statut'), '']">
            @forelse ($bookings as $booking)
                <tr>
                    <td class="px-4 py-3 font-medium text-navy-900 tabular">
                        <a href="{{ route('admin.bookings.show', $booking) }}" class="hover:underline">{{ $booking->reference }}</a>
                    </td>
                    <td class="px-4 py-3 text-navy-600">{{ $booking->user?->full_name }}</td>
                    <td class="px-4 py-3 text-navy-600">{{ $booking->property?->name }}</td>
                    <td class="px-4 py-3 text-navy-500 tabular">
                        {{ $booking->checkin_date->format('d/m/y') }} → {{ $booking->checkout_date->format('d/m/y') }}
                        <span class="text-navy-400">({{ $booking->nights }}n)</span>
                    </td>
                    <td class="px-4 py-3 font-medium text-navy-900 tabular">{{ $booking->total_amount->format() }}</td>
                    <td class="px-4 py-3">
                        @if ($booking->payment)
                            <x-ui.badge :variant="$booking->payment->status->color()">{{ $booking->payment->status->label() }}</x-ui.badge>
                        @else
                            <span class="text-navy-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3"><x-ui.badge :variant="$booking->status->color()">{{ $booking->status->label() }}</x-ui.badge></td>
                    <td class="px-4 py-3 text-right">
                        <x-ui.button :href="route('admin.bookings.show', $booking)" variant="ghost" size="sm">{{ __('Détail') }}</x-ui.button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-10 text-center text-navy-400">{{ __('Aucune réservation.') }}</td></tr>
            @endforelse
        </x-admin.table>
    </x-admin.panel>

    <div class="mt-6">{{ $bookings->links() }}</div>
</x-layouts.admin>
