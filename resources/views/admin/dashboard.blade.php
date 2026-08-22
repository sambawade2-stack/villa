<x-layouts.admin :title="__('Tableau de bord')" :heading="__('Tableau de bord')">
    <x-slot:actions>
        <x-ui.button :href="route('admin.villas.create')" size="sm" icon="plus">{{ __('Ajouter une villa') }}</x-ui.button>
        <x-ui.button :href="route('admin.villas.index')" variant="outline" size="sm">{{ __('Gérer les villas') }}</x-ui.button>
    </x-slot:actions>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['key', __('Villas'), $stats['properties'],
                trans_choice(':count publiée|:count publiées', $stats['published'], ['count' => $stats['published']])
                .' · '.trans_choice(':count brouillon|:count brouillons', $stats['drafts'], ['count' => $stats['drafts']])],
            ['calendar', __('Réservations'), $stats['bookings'], __('toutes périodes')],
            ['users', __('Clients'), $stats['customers'], trans_choice(':count propriétaire|:count propriétaires', $stats['owners'], ['count' => $stats['owners']])],
            ['home', __('Chiffre d\'affaires'), $stats['revenue']->format(withCurrency: false), __(':amount de commissions', ['amount' => $stats['commissions']->format()])],
        ] as [$icon, $label, $value, $hint])
            <article class="rounded-card border border-stone-200 bg-white p-5">
                <div class="flex items-center gap-3">
                    <span class="flex size-9 items-center justify-center rounded-lg bg-navy-50 text-navy-600">
                        <x-ui.icon :name="$icon" class="size-4.5" />
                    </span>
                    <p class="text-sm text-navy-500">{{ $label }}</p>
                </div>
                <p class="mt-3 text-2xl font-bold text-navy-900 tabular">{{ $value }}</p>
                <p class="mt-1 text-xs text-navy-400">{{ $hint }}</p>
            </article>
        @endforeach
    </div>

    {{-- ------------------------------------------------------- Conformité --}}
    <section class="mt-8">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-base font-semibold text-navy-900">{{ __('Conformité') }}</h2>
            <x-ui.button :href="route('admin.villas.index')" variant="ghost" size="sm" icon-after="arrow-right">
                {{ __('Voir les villas') }}
            </x-ui.button>
        </div>

        <div class="mt-3 grid gap-4 lg:grid-cols-2">
            <article class="rounded-card border border-stone-200 bg-white p-5">
                <p class="text-sm text-navy-500">{{ __('Villas publiées sans dossier complet') }}</p>
                <p class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-navy-900 tabular">{{ $unverified }}</span>
                    @if ($unverified === 0)
                        <x-ui.badge variant="success" icon="badge-check">{{ __('Tout est en règle') }}</x-ui.badge>
                    @else
                        <x-ui.badge variant="warning">{{ __('à compléter') }}</x-ui.badge>
                    @endif
                </p>
                <p class="mt-2 text-xs leading-relaxed text-navy-400">
                    {{ __('Le badge « Villa vérifiée » ne s\'active que lorsque toutes les pièces requises sont contrôlées.') }}
                </p>
            </article>

            <article class="rounded-card border border-stone-200 bg-white p-5">
                <p class="text-sm text-navy-500">{{ __('Pièces refusées ou périmées') }}</p>
                @if ($complianceAlerts->isEmpty())
                    <p class="mt-3 text-sm text-navy-400">{{ __('Aucune pièce n\'appelle d\'action.') }}</p>
                @else
                    <ul class="mt-3 flex flex-col divide-y divide-stone-100">
                        @foreach ($complianceAlerts as $alert)
                            <li class="flex items-center justify-between gap-3 py-2 text-sm">
                                <a href="{{ route('admin.villas.compliance.show', $alert->property) }}"
                                   class="min-w-0 truncate text-navy-700 hover:text-navy-900 hover:underline">
                                    {{ $alert->property?->name }} — {{ $alert->item->label() }}
                                </a>
                                <x-ui.badge :variant="$alert->status->color()">{{ $alert->status->label() }}</x-ui.badge>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
        </div>
    </section>

    {{-- --------------------------------------------------- Dernières résas --}}
    <section class="mt-8">
        <h2 class="text-base font-semibold text-navy-900">{{ __('Dernières réservations') }}</h2>

        <div class="mt-3 overflow-x-auto rounded-card border border-stone-200 bg-white">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-stone-200 bg-stone-50 text-left text-xs uppercase tracking-wider text-navy-400">
                        <th class="px-4 py-3 font-medium">{{ __('Référence') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Client') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Villa') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Dates') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Montant') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Statut') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($recentBookings as $booking)
                        <tr>
                            <td class="px-4 py-3 font-medium text-navy-900 tabular">{{ $booking->reference }}</td>
                            <td class="px-4 py-3 text-navy-600">{{ $booking->user?->full_name }}</td>
                            <td class="px-4 py-3 text-navy-600">{{ $booking->property?->name }}</td>
                            <td class="px-4 py-3 text-navy-500 tabular">
                                {{ $booking->checkin_date->format('d/m/Y') }} → {{ $booking->checkout_date->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 font-medium text-navy-900 tabular">{{ $booking->total_amount->format() }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$booking->status->color()">{{ $booking->status->label() }}</x-ui.badge>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-navy-400">{{ __('Aucune réservation.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.admin>
