<x-layouts.admin :title="$owner->full_name" :heading="$owner->full_name">
    <x-slot:actions>
        <x-ui.button :href="route('admin.owners.edit', $owner)" variant="outline" size="sm">{{ __('Modifier') }}</x-ui.button>
        <x-ui.button :href="route('admin.owners.index')" variant="ghost" size="sm" icon="chevron-left">{{ __('Retour') }}</x-ui.button>
        <form method="POST" action="{{ route('admin.owners.destroy', $owner) }}"
              onsubmit="return confirm('{{ __('Supprimer définitivement ce propriétaire ? Impossible si des villas lui sont encore rattachées.') }}')">
            @csrf @method('DELETE')
            <x-ui.button type="submit" variant="ghost" size="sm" class="text-red-600 hover:bg-red-50">{{ __('Supprimer') }}</x-ui.button>
        </form>
    </x-slot:actions>

    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ([
            [__('Villas'), $owner->properties->count(), __('au catalogue')],
            [__('Réservations'), $bookingsCount, __('toutes périodes')],
            [__('Revenus générés'), $revenue->format(withCurrency: false).' FCFA', __(':amount à reverser', ['amount' => $payout->format()])],
        ] as [$label, $value, $hint])
            <article class="rounded-card border border-stone-200 bg-white p-5">
                <p class="text-sm text-navy-500">{{ $label }}</p>
                <p class="mt-2 text-2xl font-bold text-navy-900 tabular">{{ $value }}</p>
                <p class="mt-1 text-xs text-navy-400">{{ $hint }}</p>
            </article>
        @endforeach
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-[20rem_1fr]">
        <x-admin.panel :title="__('Coordonnées')">
            {{-- Rappel : rien de ce bloc n'apparaît sur le site public. --}}
            <dl class="divide-y divide-stone-100 text-sm">
                @foreach ([
                    [__('Téléphone'), $owner->phone],
                    [__('WhatsApp'), $owner->whatsapp],
                    [__('E-mail'), $owner->email],
                    [__('Ville'), $owner->city],
                    [__('Adresse interne'), $owner->internal_address],
                ] as [$label, $value])
                    <div class="flex justify-between gap-4 px-5 py-3">
                        <dt class="text-navy-400">{{ $label }}</dt>
                        <dd class="text-right text-navy-800">{{ $value ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($owner->internal_notes)
                <div class="border-t border-stone-200 bg-stone-50 px-5 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-navy-400">{{ __('Notes internes') }}</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-navy-600">{{ $owner->internal_notes }}</p>
                </div>
            @endif

            <div class="flex flex-wrap gap-2 border-t border-stone-200 p-4">
                @if ($owner->whatsapp)
                    <x-whatsapp-button size="sm" :number="$owner->whatsapp">{{ __('WhatsApp') }}</x-whatsapp-button>
                @endif
                <x-ui.button :href="'tel:'.preg_replace('/\s+/', '', $owner->phone)" variant="outline" size="sm" icon="phone">
                    {{ __('Appeler') }}
                </x-ui.button>
            </div>

            <div class="border-t border-stone-200 p-5">
                <p class="text-sm font-semibold text-navy-900">{{ __('Accès portail propriétaire') }}</p>
                @if ($owner->hasAccount())
                    <p class="mt-2">
                        <x-ui.badge variant="success" icon="badge-check">{{ __('Accès activé') }}</x-ui.badge>
                    </p>
                    <p class="mt-2 text-xs leading-relaxed text-navy-400">
                        {{ __('Ce propriétaire peut suivre ses villas et bloquer ses propres dates depuis son espace.') }}
                    </p>
                @else
                    <p class="mt-2 text-xs leading-relaxed text-navy-400">
                        {{ __('Donne à ce propriétaire un accès pour suivre l\'état de sa villa et bloquer ses propres dates.') }}
                    </p>
                    <form method="POST" action="{{ route('admin.owners.grant-access', $owner) }}" class="mt-3">
                        @csrf
                        <x-ui.button type="submit" size="sm" :disabled="! $owner->email">
                            {{ __('Activer l\'accès') }}
                        </x-ui.button>
                    </form>
                    @unless ($owner->email)
                        <p class="mt-2 text-xs text-danger-600">{{ __('Renseignez une adresse e-mail pour pouvoir l\'activer.') }}</p>
                    @endunless
                @endif
            </div>
        </x-admin.panel>

        <x-admin.panel :title="__('Villas de ce propriétaire')">
            <x-admin.table :headers="[__('Villa'), __('Destination'), __('Prix / nuit'), __('Statut'), __('Conformité'), '']">
                @forelse ($owner->properties as $property)
                    <tr>
                        <td class="px-4 py-3 font-medium text-navy-900">{{ $property->name }}</td>
                        <td class="px-4 py-3 text-navy-600">{{ $property->destination?->name }}</td>
                        <td class="px-4 py-3 text-navy-900 tabular">{{ $property->base_price->format() }}</td>
                        <td class="px-4 py-3"><x-ui.badge :variant="$property->status->color()">{{ $property->status->label() }}</x-ui.badge></td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$property->is_verified ? 'success' : 'warning'">
                                {{ $property->is_verified ? __('Vérifiée') : __('Incomplète') }}
                            </x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <x-ui.button :href="route('admin.villas.compliance.show', $property)" variant="ghost" size="sm">
                                {{ __('Dossier') }}
                            </x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-navy-400">{{ __('Aucune villa rattachée.') }}</td></tr>
                @endforelse
            </x-admin.table>
        </x-admin.panel>
    </div>
</x-layouts.admin>
