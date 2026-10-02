@php
    use App\Enums\ComplianceItem;
    use App\Enums\ComplianceStatus;
    use App\Services\Compliance\ComplianceService;

    $badge = match ($summary['status']) {
        'verified' => ['success', '🟢', __('Vérifiée')],
        'blocked' => ['danger', '🔴', __('Bloquée')],
        default => ['warning', '🟠', __('En cours')],
    };
@endphp

<x-layouts.admin :title="__('Conformité — :villa', ['villa' => $property->name])"
                 :heading="__('Documents de conformité')">
    <x-slot:actions>
        <x-ui.button :href="route('admin.villas.index')" variant="ghost" size="sm" icon="chevron-left">
            {{ __('Retour aux villas') }}
        </x-ui.button>
    </x-slot:actions>

    {{-- ------------------------------------------------------------ En-tête --}}
    <div class="rounded-card border border-stone-200 bg-white p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <span class="size-14 shrink-0 overflow-hidden rounded-lg bg-stone-100">
                    @if ($property->primaryImage)
                        <img src="{{ $property->primaryImage->url('thumb') }}" alt="" class="size-full object-cover">
                    @endif
                </span>
                <div>
                    <p class="text-xs uppercase tracking-wider text-navy-400">{{ __('Villa') }}</p>
                    <h2 class="text-lg font-semibold text-navy-900">{{ $property->name }}</h2>
                    <p class="text-sm text-navy-500">
                        {{ $property->destination?->name }}
                        @if ($property->owner) · {{ __('Propriétaire : :name', ['name' => $property->owner->full_name]) }} @endif
                    </p>
                </div>
            </div>

            <div class="text-right">
                <p class="text-xs uppercase tracking-wider text-navy-400">{{ __('Statut') }}</p>
                <p class="mt-1 flex items-center justify-end gap-2">
                    <span aria-hidden="true" class="text-lg leading-none">{{ $badge[1] }}</span>
                    <x-ui.badge :variant="$badge[0]">{{ $badge[2] }}</x-ui.badge>
                </p>
                <p class="mt-1.5 text-sm text-navy-500 tabular">
                    {{ __(':done sur :total pièces en règle', ['done' => $summary['satisfied'], 'total' => $summary['total']]) }}
                </p>
            </div>
        </div>

        <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-stone-100">
            <div class="h-full rounded-full {{ $summary['status'] === 'verified' ? 'bg-success-500' : 'bg-amber-400' }}"
                 style="width: {{ $summary['total'] > 0 ? round($summary['satisfied'] / $summary['total'] * 100) : 0 }}%"></div>
        </div>
    </div>

    {{-- Rappel permanent : ces pièces ne sortent jamais de cet écran. --}}
    <x-ui.alert variant="info" class="mt-5" :title="__('Documents strictement internes')">
        {{ __('Ces pièces ne sont ni affichées sur le site, ni transmises aux voyageurs, ni indexées. Elles sont stockées hors du dossier public et ne sont consultables que depuis cet écran, par un administrateur connecté.') }}
    </x-ui.alert>

    {{-- ------------------------------------------------------- Liste des pièces --}}
    <div class="mt-5 flex flex-col gap-3">
        @foreach ($checks as $check)
            @php
                $satisfied = $check->status->satisfies();
                $alert = $check->status->needsAttention() || $check->hasExpired();
            @endphp

            <section x-data="{ open: false }"
                     @class([
                         'rounded-card border bg-white',
                         'border-danger-500/35' => $alert,
                         'border-stone-200' => ! $alert,
                     ])>

                <div class="flex flex-wrap items-center gap-4 p-4">
                    <span @class([
                        'flex size-9 shrink-0 items-center justify-center rounded-lg',
                        'bg-success-50 text-success-500' => $satisfied,
                        'bg-danger-50 text-danger-500' => $alert,
                        'bg-stone-100 text-navy-400' => ! $satisfied && ! $alert,
                    ])>
                        <x-ui.icon :name="$satisfied ? 'check' : $check->item->icon()" class="size-4.5" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-center gap-2 font-medium text-navy-900">
                            {{ $check->item->label() }}
                            @if ($check->item->isConditional())
                                <span class="text-xs font-normal text-navy-400">{{ __('si applicable') }}</span>
                            @endif
                        </p>

                        <p class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-navy-500">
                            @if ($check->item === ComplianceItem::OwnerIdentity)
                                <span class="text-navy-400">
                                    {{ $property->owner?->cni_number
                                        ? __('CNI n° :number', ['number' => $property->owner->cni_number])
                                        : __('Numéro de CNI non renseigné') }}
                                </span>
                            @elseif ($check->item === ComplianceItem::RentalTerms)
                                <span class="text-navy-400">
                                    {{ filled($property->house_rules?->get())
                                        ? __('Renseignées (onglet Règles)')
                                        : __('Non renseignées — onglet Règles de la fiche villa') }}
                                </span>
                            @elseif ($check->hasDocument())
                                <span class="inline-flex items-center gap-1">
                                    <x-ui.icon name="check" class="size-3.5 text-navy-400" />
                                    {{ $check->document_name }} ({{ $check->humanSize() }})
                                </span>
                            @elseif ($check->item->requiresDocument())
                                <span class="text-navy-400">{{ __('Aucune pièce déposée') }}</span>
                            @else
                                <span class="text-navy-400">{{ __('Contrôle effectué par l\'équipe, sans document') }}</span>
                            @endif

                            @if ($check->reference)
                                <span>{{ __('Réf. :ref', ['ref' => $check->reference]) }}</span>
                            @endif

                            @if ($check->expires_on)
                                <span @class(['tabular', 'text-danger-700 font-medium' => $check->hasExpired(), 'text-warning-700 font-medium' => $check->expiresSoon()])>
                                    {{ $check->hasExpired() ? __('Périmé le :date', ['date' => $check->expires_on->format('d/m/Y')])
                                                            : __('Valide jusqu\'au :date', ['date' => $check->expires_on->format('d/m/Y')]) }}
                                </span>
                            @endif

                            @if ($check->verified_at)
                                <span>{{ __('Vérifié le :date par :who', [
                                    'date' => $check->verified_at->format('d/m/Y'),
                                    'who' => $check->verifier?->first_name ?? __('l\'équipe'),
                                ]) }}</span>
                            @endif
                        </p>
                    </div>

                    <x-ui.badge :variant="$check->status->color()">{{ $check->status->label() }}</x-ui.badge>

                    <div class="flex items-center gap-1.5">
                        @if ($check->hasDocument())
                            <x-ui.button :href="route('admin.villas.compliance.download', [$property, $check])"
                                         variant="outline" size="sm" icon="eye" target="_blank" rel="noopener">
                                {{ __('Ouvrir') }}
                            </x-ui.button>
                        @endif
                        <x-ui.button @click="open = ! open" variant="ghost" size="sm" icon-after="chevron-down">
                            {{ __('Gérer') }}
                        </x-ui.button>
                    </div>
                </div>

                {{-- ------------------------------------------------ Panneau --}}
                <div x-show="open" x-cloak x-collapse class="border-t border-stone-200 bg-stone-50 p-4">
                    @if ($check->item === ComplianceItem::OwnerIdentity)
                        {{-- Des champs simples, jamais un document : le nom et
                             l'adresse viennent de la fiche propriétaire (lecture
                             seule, une seule saisie) ; seul le numéro de CNI
                             s'enregistre ici. --}}
                        <form method="POST" action="{{ route('admin.villas.compliance.identity', [$property, $check]) }}"
                              class="flex flex-col gap-3 lg:max-w-md">
                            @csrf @method('PATCH')
                            <h3 class="text-sm font-semibold text-navy-900">{{ __('Identité du propriétaire') }}</h3>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <x-ui.input :label="__('Prénom')" :value="$property->owner?->first_name" disabled />
                                <x-ui.input :label="__('Nom')" :value="$property->owner?->last_name" disabled />
                            </div>

                            <x-ui.input :label="__('Adresse')" :value="$property->owner?->internal_address" disabled />

                            <x-ui.input name="cni_number" :label="__('Numéro de CNI')"
                                        :value="old('cni_number', $property->owner?->cni_number)"
                                        placeholder="1 XXX XXXX XXXXX" />

                            <x-ui.select name="status" :label="__('Statut')">
                                @foreach (ComplianceStatus::cases() as $case)
                                    @continue($case === ComplianceStatus::NotApplicable)
                                    <option value="{{ $case->value }}" @selected($check->status === $case)>
                                        {{ $case->label() }}
                                    </option>
                                @endforeach
                            </x-ui.select>

                            <p class="text-xs text-navy-400">
                                {{ __('Prénom, nom et adresse se modifient depuis la fiche du propriétaire, pas ici.') }}
                            </p>

                            <x-ui.button type="submit" size="sm" class="self-start">{{ __('Enregistrer') }}</x-ui.button>
                        </form>
                    @else
                    <div class="grid gap-5 lg:grid-cols-2">
                        @if ($check->item->requiresDocument())
                            <form method="POST" enctype="multipart/form-data"
                                  action="{{ route('admin.villas.compliance.upload', [$property, $check]) }}"
                                  class="flex flex-col gap-3">
                                @csrf
                                <h3 class="text-sm font-semibold text-navy-900">{{ __('Déposer la pièce') }}</h3>

                                <input type="file" name="document" required
                                       accept=".pdf,.jpg,.jpeg,.png,.webp"
                                       class="block w-full text-sm text-navy-600
                                              file:mr-3 file:rounded-lg file:border-0 file:bg-navy-800 file:px-4 file:py-2
                                              file:text-sm file:font-medium file:text-white hover:file:bg-navy-700">

                                <p class="text-xs text-navy-400">
                                    {{ __('PDF ou image, :size Mo maximum. Le fichier est renommé et stocké hors du dossier public.', [
                                        'size' => (int) (ComplianceService::MAX_SIZE_KB / 1024),
                                    ]) }}
                                </p>
                                @error('document') <p class="text-xs text-danger-700">{{ $message }}</p> @enderror

                                <div class="flex items-center gap-2">
                                    <x-ui.button type="submit" size="sm">{{ __('Déposer') }}</x-ui.button>

                                    @if ($check->hasDocument())
                                        <button type="button"
                                                form="delete-{{ $check->id }}"
                                                class="text-sm text-danger-700 hover:underline"
                                                onclick="this.form.requestSubmit()">
                                            {{ __('Supprimer la pièce') }}
                                        </button>
                                    @endif
                                </div>
                            </form>

                            @if ($check->hasDocument())
                                <form id="delete-{{ $check->id }}" method="POST" class="hidden"
                                      action="{{ route('admin.villas.compliance.document.destroy', [$property, $check]) }}"
                                      onsubmit="return confirm('{{ __('Supprimer définitivement cette pièce ?') }}')">
                                    @csrf @method('DELETE')
                                </form>
                            @endif
                        @endif

                        <form method="POST" action="{{ route('admin.villas.compliance.status', [$property, $check]) }}"
                              class="flex flex-col gap-3 {{ $check->item->requiresDocument() ? '' : 'lg:col-span-2' }}">
                            @csrf @method('PATCH')
                            <h3 class="text-sm font-semibold text-navy-900">{{ __('Contrôle') }}</h3>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <x-ui.select name="status" :label="__('Statut')">
                                    @foreach (ComplianceStatus::cases() as $case)
                                        @continue($case === ComplianceStatus::NotApplicable && ! $check->item->isConditional())
                                        <option value="{{ $case->value }}" @selected($check->status === $case)>
                                            {{ $case->label() }}
                                        </option>
                                    @endforeach
                                </x-ui.select>

                                <x-ui.input name="reference" :label="__('Référence (facultatif)')"
                                            :value="old('reference', $check->reference)" placeholder="{{ __('N° RCCM, agrément…') }}" />
                            </div>

                            @if ($check->item->canExpire())
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <x-ui.input type="date" name="issued_on" :label="__('Délivré le')"
                                                :value="old('issued_on', $check->issued_on?->format('Y-m-d'))" />
                                    <x-ui.input type="date" name="expires_on" :label="__('Valide jusqu\'au')"
                                                :value="old('expires_on', $check->expires_on?->format('Y-m-d'))" />
                                </div>
                            @endif

                            <div class="flex flex-col gap-1.5">
                                <label for="notes-{{ $check->id }}" class="field-label">
                                    {{ __('Notes internes') }}
                                </label>
                                <textarea id="notes-{{ $check->id }}" name="notes" rows="2" maxlength="2000"
                                          class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm text-navy-900 focus:border-navy-500"
                                          placeholder="{{ __('Visible uniquement en administration.') }}">{{ $check->notes }}</textarea>
                            </div>

                            <x-ui.button type="submit" size="sm" class="self-start">{{ __('Enregistrer') }}</x-ui.button>
                        </form>
                    </div>
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    <p class="mt-6 text-xs leading-relaxed text-navy-400">
        {{ __('Le badge « Villa vérifiée » affiché sur le site découle directement de ce dossier : il s\'active lorsque toutes les pièces requises sont vérifiées, et s\'éteint dès qu\'une pièce est refusée ou périmée. Il ne se coche pas à la main.') }}
    </p>
</x-layouts.admin>
