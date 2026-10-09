<x-layouts.admin :title="__('Destinations')" :heading="__('Destinations')">
    <x-slot:actions>
        <x-ui.button :href="route('admin.destinations.create')" size="sm" icon="plus">
            {{ __('Nouvelle destination') }}
        </x-ui.button>
    </x-slot:actions>

    <div class="overflow-x-auto rounded-card border border-stone-200 bg-white">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-stone-200 bg-stone-50 text-left text-xs uppercase tracking-wider text-navy-400">
                    <th class="px-4 py-3 font-medium">{{ __('Destination') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Région') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Villas') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Statut') }}</th>
                    <th class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($destinations as $destination)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="font-medium text-navy-900">{{ $destination->name->get() }}</span>
                            <p class="text-xs text-navy-400">/{{ $destination->slug }}</p>
                        </td>
                        <td class="px-4 py-3 text-navy-600">{{ $destination->region }}</td>
                        <td class="px-4 py-3 text-navy-600 tabular">{{ $destination->properties_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5">
                                <x-ui.badge :variant="$destination->is_active ? 'success' : 'neutral'">
                                    {{ $destination->is_active ? __('Active') : __('Inactive') }}
                                </x-ui.badge>
                                @if ($destination->is_featured)
                                    <x-ui.badge variant="warning">{{ __('En avant') }}</x-ui.badge>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                <x-ui.button :href="route('admin.destinations.edit', $destination)" variant="outline" size="sm">
                                    {{ __('Modifier') }}
                                </x-ui.button>
                                <form method="POST" action="{{ route('admin.destinations.destroy', $destination) }}"
                                      onsubmit="return confirm('{{ __('Supprimer cette destination ?') }}')">
                                    @csrf @method('DELETE')
                                    <x-ui.button type="submit" variant="ghost" size="sm" class="text-red-600 hover:bg-red-50">
                                        {{ __('Supprimer') }}
                                    </x-ui.button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-navy-400">{{ __('Aucune destination.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
