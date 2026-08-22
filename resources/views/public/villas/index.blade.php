@php
    $sorts = [
        'pertinence' => __('Recommandées'),
        'prix-croissant' => __('Prix croissant'),
        'prix-decroissant' => __('Prix décroissant'),
        'note' => __('Meilleures notes'),
        'nouveautes' => __('Nouveautés'),
    ];

    $currentSort = $filters['sort'] ?? 'pertinence';
    $selectedAmenities = $filters['amenities'] ?? [];
@endphp

<x-layouts.public
    :title="__('Villas à louer sur la Petite Côte — Petite Côte Villas')"
    :description="__('Parcourez les villas disponibles à Saly, Mbour, Ngaparou, Somone, Popenguine et Joal-Fadiouth. Filtrez par dates, capacité, budget et équipements.')"
>
    <div class="border-b border-stone-200 bg-white">
        <div class="container-page py-6">
            <x-ui.breadcrumb :items="[
                ['label' => __('Accueil'), 'url' => route('home')],
                ['label' => __('Villas')],
            ]" />
            <h1 class="mt-3 text-3xl text-navy-900 lg:text-display">{{ __('Explorer les villas') }}</h1>
            <p class="mt-1.5 text-navy-500 tabular">
                {{ trans_choice(':count villa disponible|:count villas disponibles', $properties->total(), ['count' => $properties->total()]) }}
            </p>

            <x-search-box compact class="mt-6 shadow-card" />
        </div>
    </div>

    <div class="container-page py-8">
        @if ($errors->any())
            <x-ui.alert variant="warning" :title="__('Recherche ajustée')" class="mb-6">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $message) <li>{{ $message }}</li> @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <div class="grid gap-8 lg:grid-cols-[17rem_1fr]">
            {{-- ------------------------------------------------------- Filtres --}}
            <aside x-data="{ open: false }" class="lg:sticky lg:top-24 lg:self-start">
                <button type="button" @click="open = ! open"
                        class="flex w-full items-center justify-between rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm font-medium text-navy-900 lg:hidden">
                    <span class="inline-flex items-center gap-2">
                        {{ __('Filtres') }}
                        @if ($activeCount > 0)
                            <x-ui.badge variant="navy">{{ $activeCount }}</x-ui.badge>
                        @endif
                    </span>
                    <x-ui.icon name="chevron-down" class="size-4" ::class="open && 'rotate-180'" />
                </button>

                <form method="GET" action="{{ route('villas.index') }}"
                      x-show="open || window.innerWidth >= 1024" x-cloak
                      class="mt-3 flex flex-col gap-6 rounded-2xl border border-stone-200 bg-white p-5 lg:mt-0 lg:!block lg:space-y-6">

                    {{-- Les critères de la barre de recherche restent actifs au filtrage. --}}
                    @foreach (['destination', 'checkin', 'checkout', 'guests'] as $carried)
                        @if (! empty($filters[$carried]))
                            <input type="hidden" name="{{ $carried }}" value="{{ $filters[$carried] }}">
                        @endif
                    @endforeach
                    @if ($currentSort !== 'pertinence')
                        <input type="hidden" name="sort" value="{{ $currentSort }}">
                    @endif

                    <fieldset class="flex flex-col gap-3">
                        <legend class="field-label">{{ __('Budget par nuit') }}</legend>
                        <div class="grid grid-cols-2 gap-2">
                            <x-ui.input type="number" name="price_min" :label="__('Minimum')" min="0" step="5000"
                                        placeholder="0" :value="$filters['price_min'] ?? null" />
                            <x-ui.input type="number" name="price_max" :label="__('Maximum')" min="0" step="5000"
                                        placeholder="500 000" :value="$filters['price_max'] ?? null" />
                        </div>
                        <p class="text-xs text-navy-400">{{ __('En francs CFA.') }}</p>
                    </fieldset>

                    <fieldset class="grid grid-cols-2 gap-2">
                        <legend class="sr-only">{{ __('Composition') }}</legend>
                        <x-ui.select name="bedrooms" :label="__('Chambres')">
                            <option value="">{{ __('Toutes') }}</option>
                            @foreach ([1, 2, 3, 4, 5, 6] as $n)
                                <option value="{{ $n }}" @selected((int) ($filters['bedrooms'] ?? 0) === $n)>{{ $n }}+</option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.select name="bathrooms" :label="__('Salles de bain')">
                            <option value="">{{ __('Toutes') }}</option>
                            @foreach ([1, 2, 3, 4] as $n)
                                <option value="{{ $n }}" @selected((int) ($filters['bathrooms'] ?? 0) === $n)>{{ $n }}+</option>
                            @endforeach
                        </x-ui.select>
                    </fieldset>

                    <fieldset class="flex flex-col gap-2.5">
                        <legend class="mb-1 field-label">{{ __('Équipements') }}</legend>
                        @foreach ($amenities as $amenity)
                            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-navy-700">
                                <input type="checkbox" name="amenities[]" value="{{ $amenity->slug }}"
                                       @checked(in_array($amenity->slug, $selectedAmenities, true))
                                       class="size-4 rounded border-stone-400 text-navy-900 focus:ring-navy-500">
                                <x-ui.icon :name="$amenity->icon ?? 'check'" class="size-4 text-navy-400" />
                                {{ $amenity->name }}
                            </label>
                        @endforeach
                    </fieldset>

                    <label class="flex cursor-pointer items-center gap-2.5 border-t border-stone-200 pt-4 text-sm text-navy-700">
                        <input type="checkbox" name="verified" value="1" @checked(! empty($filters['verified']))
                               class="size-4 rounded border-stone-400 text-navy-900 focus:ring-navy-500">
                        <x-ui.icon name="badge-check" class="size-4 text-amber-500" />
                        {{ __('Villas vérifiées uniquement') }}
                    </label>

                    <div class="flex flex-col gap-2 border-t border-stone-200 pt-4">
                        <x-ui.button type="submit" class="w-full">{{ __('Appliquer les filtres') }}</x-ui.button>
                        @if ($activeCount > 0)
                            <x-ui.button :href="route('villas.index')" variant="ghost" size="sm" class="w-full">
                                {{ __('Tout effacer') }}
                            </x-ui.button>
                        @endif
                    </div>
                </form>
            </aside>

            {{-- ------------------------------------------------------ Résultats --}}
            <div>
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-navy-500 tabular">
                        {{ __('Page :current sur :last', ['current' => $properties->currentPage(), 'last' => max(1, $properties->lastPage())]) }}
                    </p>

                    <form method="GET" action="{{ route('villas.index') }}" class="flex items-center gap-2">
                        @foreach ($filters as $key => $value)
                            @continue($key === 'sort' || $value === null || $value === '')
                            @if (is_array($value))
                                @foreach ($value as $item)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach

                        <label for="sort" class="text-sm text-navy-500">{{ __('Trier par') }}</label>
                        <x-ui.select id="sort" name="sort" onchange="this.form.submit()" class="!py-2 text-sm">
                            @foreach ($sorts as $value => $label)
                                <option value="{{ $value }}" @selected($currentSort === $value)>{{ $label }}</option>
                            @endforeach
                        </x-ui.select>
                        <noscript><x-ui.button type="submit" size="sm" variant="outline">{{ __('Trier') }}</x-ui.button></noscript>
                    </form>
                </div>

                @if ($properties->isEmpty())
                    <x-ui.empty-state :title="__('Aucune villa ne correspond à cette recherche')">
                        {{ __('Essayez d\'élargir vos dates, votre budget ou de retirer un équipement.') }}
                        <x-slot:action>
                            <x-ui.button :href="route('villas.index')" variant="outline">{{ __('Réinitialiser la recherche') }}</x-ui.button>
                        </x-slot:action>
                    </x-ui.empty-state>
                @else
                    <div class="flex flex-col gap-3">
                        @foreach ($properties as $property)
                            <x-villa-card-compact :property="$property" :eager="$loop->index < 4"
                            class="reveal" :data-delay="min($loop->index, 3)" />
                        @endforeach
                    </div>

                    <div class="mt-10">
                        {{ $properties->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.public>
