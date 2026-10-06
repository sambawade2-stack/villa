<x-layouts.admin :title="__('Corbeille — Villas')" :heading="__('Corbeille')">
    <x-slot:actions>
        <x-ui.button :href="route('admin.villas.index')" variant="ghost" size="sm" icon="chevron-left">
            {{ __('Retour aux villas') }}
        </x-ui.button>
    </x-slot:actions>

    <p class="text-sm text-navy-500">
        {{ __('Villas retirées du catalogue. Restaure-les pour les récupérer, ou supprime-les définitivement — impossible si elles portent des réservations enregistrées.') }}
    </p>

    <div class="mt-5 overflow-x-auto rounded-card border border-stone-200 bg-white">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-stone-200 bg-stone-50 text-left text-xs uppercase tracking-wider text-navy-400">
                    <th class="px-4 py-3 font-medium">{{ __('Villa') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Destination') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Propriétaire') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Supprimée le') }}</th>
                    <th class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($properties as $property)
                    <tr>
                        <td class="px-4 py-3 font-medium text-navy-900">{{ $property->name }}</td>
                        <td class="px-4 py-3 text-navy-600">{{ $property->destination?->name }}</td>
                        <td class="px-4 py-3 text-navy-600">{{ $property->owner?->full_name }}</td>
                        <td class="px-4 py-3 text-navy-500 tabular">{{ $property->deleted_at?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                <form method="POST" action="{{ route('admin.villas.restore', $property) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="outline" size="sm">{{ __('Restaurer') }}</x-ui.button>
                                </form>
                                <form method="POST" action="{{ route('admin.villas.force-destroy', $property) }}"
                                      onsubmit="return confirm('{{ __('Supprimer définitivement cette villa et ses photos ? Aucun retour possible.') }}')">
                                    @csrf @method('DELETE')
                                    <x-ui.button type="submit" variant="ghost" size="sm" class="text-red-600 hover:bg-red-50">
                                        {{ __('Supprimer définitivement') }}
                                    </x-ui.button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-navy-400">{{ __('Corbeille vide.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $properties->onEachSide(1)->links() }}</div>
</x-layouts.admin>
