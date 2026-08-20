<x-layouts.admin :title="__('Clients')" :heading="__('Clients')">
    <form method="GET" class="flex items-center gap-2">
        <x-ui.input name="q" icon="search" :value="request('q')"
                    placeholder="{{ __('Nom, e-mail, téléphone…') }}" class="w-80" />
        <x-ui.button type="submit" variant="outline">{{ __('Rechercher') }}</x-ui.button>
    </form>

    <x-admin.panel class="mt-5">
        <x-admin.table :headers="[__('Client'), __('Contact'), __('Réservations'), __('Favoris'), __('Avis'), __('Inscrit le')]">
            @forelse ($customers as $customer)
                <tr>
                    <td class="px-4 py-3">
                        <p class="font-medium text-navy-900">{{ $customer->full_name }}</p>
                        <p class="text-xs text-navy-400">{{ $customer->email }}</p>
                    </td>
                    <td class="px-4 py-3 text-navy-600 tabular">{{ $customer->phone ?? '—' }}</td>
                    <td class="px-4 py-3 text-navy-900 tabular">{{ $customer->bookings_count }}</td>
                    <td class="px-4 py-3 text-navy-600 tabular">{{ $customer->favorites_count }}</td>
                    <td class="px-4 py-3 text-navy-600 tabular">{{ $customer->reviews_count }}</td>
                    <td class="px-4 py-3 text-navy-500 tabular">{{ $customer->created_at->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-navy-400">{{ __('Aucun client.') }}</td></tr>
            @endforelse
        </x-admin.table>
    </x-admin.panel>

    <div class="mt-6">{{ $customers->links() }}</div>
</x-layouts.admin>
