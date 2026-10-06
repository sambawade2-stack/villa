@php use App\Enums\PropertyStatus; @endphp

<x-layouts.admin :title="__('Villas')" :heading="__('Villas')">
    <x-slot:actions>
        <x-ui.button :href="route('admin.villas.trashed')" variant="ghost" size="sm" icon="trash">
            {{ __('Corbeille') }}
        </x-ui.button>
        <x-ui.button :href="route('admin.villas.create')" size="sm" icon="plus">
            {{ __('Ajouter une villa') }}
        </x-ui.button>
    </x-slot:actions>

    <div class="flex flex-wrap items-center gap-3">
        <form method="GET" class="flex items-center gap-2">
            @if ($status) <input type="hidden" name="status" value="{{ $status }}"> @endif
            <x-ui.input name="q" icon="search" :value="request('q')"
                        placeholder="{{ __('Nom, quartier, destination…') }}" class="w-72" />
            <x-ui.button type="submit" variant="outline">{{ __('Rechercher') }}</x-ui.button>
        </form>

        <div class="ml-auto flex flex-wrap items-center gap-1.5">
            <a href="{{ route('admin.villas.index') }}"
               @class(['rounded-lg px-3 py-1.5 text-sm', 'bg-navy-800 text-white' => ! $status, 'text-navy-600 hover:bg-stone-100' => $status])>
                {{ __('Toutes') }}
            </a>
            @foreach (PropertyStatus::cases() as $case)
                <a href="{{ route('admin.villas.index', ['status' => $case->value]) }}"
                   @class(['rounded-lg px-3 py-1.5 text-sm', 'bg-navy-800 text-white' => $status === $case->value, 'text-navy-600 hover:bg-stone-100' => $status !== $case->value])>
                    {{ $case->label() }}
                    <span class="tabular text-xs opacity-70">{{ $counts[$case->value] ?? 0 }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="mt-5 overflow-x-auto rounded-card border border-stone-200 bg-white">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-stone-200 bg-stone-50 text-left text-xs uppercase tracking-wider text-navy-400">
                    <th class="px-4 py-3 font-medium">{{ __('Villa') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Destination') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Propriétaire') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Prix / nuit') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Statut') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Conformité') }}</th>
                    <th class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($properties as $property)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="size-10 shrink-0 overflow-hidden rounded-lg bg-stone-100">
                                    @if ($property->primaryImage)
                                        <img src="{{ $property->primaryImage->url('thumb') }}" alt="" class="size-full object-cover">
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <a href="{{ route('villas.show', [$property->destination, $property]) }}" class="font-medium text-navy-900 hover:underline">
                                        {{ $property->name }}
                                    </a>
                                    <p class="text-xs text-navy-400">{{ $property->neighborhood }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-navy-600">{{ $property->destination?->name }}</td>
                        <td class="px-4 py-3 text-navy-600">{{ $property->owner?->full_name }}</td>
                        <td class="px-4 py-3 font-medium text-navy-900 tabular">{{ $property->base_price->format() }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$property->status->color()">{{ $property->status->label() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            @if ($property->compliance_alert_count > 0)
                                <x-ui.badge variant="danger">
                                    {{ trans_choice(':count alerte|:count alertes', $property->compliance_alert_count, ['count' => $property->compliance_alert_count]) }}
                                </x-ui.badge>
                            @elseif ($property->is_verified)
                                <x-ui.badge variant="success" icon="badge-check">{{ __('Vérifiée') }}</x-ui.badge>
                            @else
                                <x-ui.badge variant="warning">
                                    {{ $property->compliance_satisfied_count }} / {{ count(\App\Enums\ComplianceItem::ordered()) }}
                                </x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                <x-ui.button :href="route('admin.villas.edit', $property)" variant="outline" size="sm">
                                    {{ __('Modifier') }}
                                </x-ui.button>
                                <x-ui.button :href="route('admin.villas.compliance.show', $property)" variant="ghost" size="sm">
                                    {{ __('Dossier') }}
                                </x-ui.button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-navy-400">{{ __('Aucune villa.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $properties->onEachSide(1)->links() }}</div>
</x-layouts.admin>
