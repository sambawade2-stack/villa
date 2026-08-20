<x-layouts.admin :title="__('Propriétaires')" :heading="__('Propriétaires')">
    <x-slot:actions>
        <x-ui.button :href="route('admin.owners.create')" size="sm" icon="plus">{{ __('Ajouter') }}</x-ui.button>
    </x-slot:actions>

    <form method="GET" class="flex items-center gap-2">
        <x-ui.input name="q" icon="search" :value="request('q')"
                    placeholder="{{ __('Nom, téléphone, e-mail…') }}" class="w-80" />
        <x-ui.button type="submit" variant="outline">{{ __('Rechercher') }}</x-ui.button>
    </form>

    <x-admin.panel class="mt-5">
        <x-admin.table :headers="[__('Propriétaire'), __('Ville'), __('Contact'), __('Villas'), __('Statut'), '']">
            @forelse ($owners as $owner)
                <tr>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.owners.show', $owner) }}" class="font-medium text-navy-900 hover:underline">
                            {{ $owner->full_name }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-navy-600">{{ $owner->city ?? '—' }}</td>
                    <td class="px-4 py-3 text-navy-600 tabular">{{ $owner->phone }}</td>
                    <td class="px-4 py-3 text-navy-900 tabular">{{ $owner->properties_count }}</td>
                    <td class="px-4 py-3">
                        <x-ui.badge :variant="$owner->status->value === 'active' ? 'success' : 'neutral'">
                            {{ $owner->status->label() }}
                        </x-ui.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <x-ui.button :href="route('admin.owners.show', $owner)" variant="ghost" size="sm">{{ __('Détail') }}</x-ui.button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-navy-400">{{ __('Aucun propriétaire.') }}</td></tr>
            @endforelse
        </x-admin.table>
    </x-admin.panel>

    <div class="mt-6">{{ $owners->links() }}</div>
</x-layouts.admin>
