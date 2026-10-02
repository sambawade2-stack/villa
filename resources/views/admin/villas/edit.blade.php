@php
    use App\Enums\PropertyStatus;
    use App\Services\Media\ImageService;

    $categoryLabels = [
        'exterieur' => __('Extérieur'), 'confort' => __('Confort'), 'cuisine' => __('Cuisine'),
        'services' => __('Services'), 'pratique' => __('Pratique'), 'general' => __('Divers'),
    ];

    $selected = $property->amenities->pluck('id')->all();
@endphp

<x-layouts.admin :title="$property->name" :heading="$property->name">
    <x-slot:actions>
        <x-ui.badge :variant="$property->status->color()">{{ $property->status->label() }}</x-ui.badge>
        <x-ui.button :href="route('admin.villas.compliance.show', $property)" variant="ghost" size="sm">
            {{ __('Dossier') }}
        </x-ui.button>
        <x-ui.button :href="route('admin.villas.index')" variant="ghost" size="sm" icon="chevron-left">
            {{ __('Retour') }}
        </x-ui.button>
    </x-slot:actions>

    {{-- ------------------------------------------------------- Publication --}}
    <x-admin.panel class="mb-5">
        <div class="flex flex-wrap items-center justify-between gap-4 p-5">
            <div class="min-w-0">
                @if ($blockers === [])
                    <p class="flex items-center gap-2 text-sm font-medium text-success-700">
                        <x-ui.icon name="circle-check" class="size-4" />
                        {{ __('La fiche est complète.') }}
                    </p>
                @else
                    <p class="flex items-center gap-2 text-sm font-medium text-warning-700">
                        <x-ui.icon name="alert-triangle" class="size-4" />
                        {{ __('Il manque encore :manques.', [
                            'manques' => implode(', ', array_map(fn ($k) => __('villas.blockers.'.$k), $blockers)),
                        ]) }}
                    </p>
                @endif

                @if ($property->isPublished())
                    <a href="{{ route('villas.show', [$property->destination, $property]) }}" target="_blank" rel="noopener"
                       class="mt-1 inline-flex items-center gap-1.5 text-xs text-navy-500 hover:text-navy-900">
                        <x-ui.icon name="globe" class="size-3.5" />{{ __('Voir la fiche en ligne') }}
                    </a>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($property->isPublished())
                    <form method="POST" action="{{ route('admin.villas.unpublish', $property) }}">
                        @csrf
                        <input type="hidden" name="status" value="unpublished">
                        <x-ui.button type="submit" variant="outline" size="sm">{{ __('Dépublier') }}</x-ui.button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.villas.publish', $property) }}">
                        @csrf
                        <x-ui.button type="submit" size="sm" icon="badge-check" :disabled="$blockers !== []">
                            {{ __('Publier') }}
                        </x-ui.button>
                    </form>
                @endif
            </div>
        </div>
    </x-admin.panel>

    <div x-data="{ tab: 'infos' }">
        {{-- Onglets --}}
        <div class="mb-5 flex flex-wrap gap-1.5 border-b border-stone-200 pb-3">
            @foreach ([
                'infos' => __('Informations'),
                'lieu' => __('Localisation'),
                'equipements' => __('Équipements'),
                'photos' => __('Photos'),
                'tarifs' => __('Tarifs'),
                'disponibilites' => __('Disponibilités'),
                'regles' => __('Règles'),
                'seo' => __('Référencement'),
            ] as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'bg-navy-800 text-white' : 'text-navy-600 hover:bg-stone-100'"
                        class="rounded-lg px-3.5 py-1.5 text-sm font-semibold">
                    {{ $label }}
                    @if ($key === 'photos')
                        <span class="ml-1 text-xs opacity-70 tabular">{{ $property->images->count() }}</span>
                    @endif
                    @if ($key === 'disponibilites' && $property->availabilityBlocks->isNotEmpty())
                        <span class="ml-1 text-xs opacity-70 tabular">{{ $property->availabilityBlocks->count() }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- ------------------------------------------------------- Photos --}}
        <div x-show="tab === 'photos'" x-cloak class="flex flex-col gap-5">
            <x-admin.panel :title="__('Photos')"
                           :subtitle="__('La première image sert de couverture. Quatre tailles sont générées automatiquement, en WebP.')">
                <form method="POST" action="{{ route('admin.villas.photos.store', $property) }}"
                      enctype="multipart/form-data" class="flex flex-col gap-3 p-5">
                    @csrf
                    <input type="file" name="photos[]" multiple required
                           accept=".jpg,.jpeg,.png,.webp"
                           class="block w-full text-sm text-navy-600
                                  file:mr-3 file:rounded-lg file:border-0 file:bg-navy-800 file:px-4 file:py-2
                                  file:text-sm file:font-medium file:text-white hover:file:bg-navy-700">
                    <p class="text-xs text-navy-400">
                        {{ __('JPEG, PNG ou WebP, :size Mo maximum par photo. Les données EXIF, qui contiennent souvent les coordonnées GPS du bien, sont supprimées à l\'envoi.', [
                            'size' => (int) (ImageService::MAX_SIZE_KB / 1024),
                        ]) }}
                    </p>
                    @error('photos.*') <p class="text-xs text-danger-700">{{ $message }}</p> @enderror
                    @error('photos') <p class="text-xs text-danger-700">{{ $message }}</p> @enderror

                    <x-ui.button type="submit" size="sm" class="self-start">{{ __('Envoyer') }}</x-ui.button>
                </form>

                @if ($property->images->isNotEmpty())
                    <div class="grid gap-4 border-t border-stone-200 p-5 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($property->images as $image)
                            <figure class="overflow-hidden rounded-card border border-stone-200">
                                <span class="relative block aspect-4/3 bg-stone-100">
                                    <img src="{{ $image->url('thumb') }}" alt="" class="size-full object-cover">
                                    @if ($image->is_primary)
                                        <span class="absolute left-2 top-2">
                                            <x-ui.badge variant="navy" icon="badge-check">{{ __('Couverture') }}</x-ui.badge>
                                        </span>
                                    @endif
                                </span>

                                <figcaption class="flex items-center justify-between gap-2 p-2">
                                    @unless ($image->is_primary)
                                        <form method="POST" action="{{ route('admin.villas.photos.primary', [$property, $image]) }}">
                                            @csrf
                                            <button type="submit" class="text-xs text-navy-600 hover:underline">
                                                {{ __('Couverture') }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-navy-400">{{ $image->humanSize() ?? '' }}</span>
                                    @endunless

                                    <form method="POST" action="{{ route('admin.villas.photos.destroy', [$property, $image]) }}"
                                          onsubmit="return confirm('{{ __('Supprimer cette photo ?') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-danger-700 hover:underline">
                                            {{ __('Supprimer') }}
                                        </button>
                                    </form>
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                @else
                    <p class="border-t border-stone-200 px-5 py-10 text-center text-sm text-navy-400">
                        {{ __('Aucune photo. Une villa sans photo ne peut pas être publiée.') }}
                    </p>
                @endif
            </x-admin.panel>
        </div>

        {{-- ------------------------------------------------ Formulaire --}}
        <form method="POST" action="{{ route('admin.villas.update', $property) }}">
            @csrf @method('PUT')

            <div x-show="tab === 'infos'" class="flex flex-col gap-5">
                <x-admin.panel :title="__('Informations')">
                    <div class="flex flex-col gap-5 p-5">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-ui.input name="name" :label="__('Nom')" required :value="old('name', $property->name)" />
                            <x-ui.input name="slug" :label="__('Identifiant d\'URL')" required
                                        :value="old('slug', $property->slug)"
                                        :hint="__('Apparaît dans l\'adresse : /villas/destination/identifiant')" />
                        </div>

                        <div class="grid gap-5 sm:grid-cols-3">
                            <x-ui.select name="type" :label="__('Type')" required>
                                @foreach ($types as $type)
                                    <option value="{{ $type->value }}" @selected(old('type', $property->type->value) === $type->value)>
                                        {{ $type->label() }}
                                    </option>
                                @endforeach
                            </x-ui.select>

                            <x-ui.select name="property_owner_id" :label="__('Propriétaire')" required>
                                @foreach ($owners as $owner)
                                    <option value="{{ $owner->id }}" @selected(old('property_owner_id', $property->property_owner_id) == $owner->id)>
                                        {{ $owner->full_name }}
                                    </option>
                                @endforeach
                            </x-ui.select>

                            <x-ui.select name="destination_id" :label="__('Destination')" required>
                                @foreach ($destinations as $destination)
                                    <option value="{{ $destination->id }}" @selected(old('destination_id', $property->destination_id) == $destination->id)>
                                        {{ $destination->name }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-4">
                            <x-ui.input type="number" name="capacity" :label="__('Voyageurs')" required min="1"
                                        :value="old('capacity', $property->capacity)" class="no-spinner" />
                            <x-ui.input type="number" name="bedrooms" :label="__('Chambres')" required min="1"
                                        :value="old('bedrooms', $property->bedrooms)" class="no-spinner" />
                            <x-ui.input type="number" name="beds" :label="__('Lits')" required min="1"
                                        :value="old('beds', $property->beds)" class="no-spinner" />
                            <x-ui.input type="number" name="bathrooms" :label="__('Salles de bain')" required min="1"
                                        :value="old('bathrooms', $property->bathrooms)" class="no-spinner" />
                        </div>

                        <x-ui.input type="number" name="surface_sqm" :label="__('Surface (m², facultatif)')" min="10"
                                    :value="old('surface_sqm', $property->surface_sqm)" class="no-spinner sm:w-56" />
                    </div>
                </x-admin.panel>

                <x-admin.panel :title="__('Descriptions')"
                               :subtitle="__('Le français est obligatoire pour publier. L\'anglais peut venir plus tard.')">
                    <div class="grid gap-5 p-5 lg:grid-cols-2">
                        @foreach ([['fr', __('Français')], ['en', __('Anglais')]] as [$locale, $label])
                            <div class="flex flex-col gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label for="short_{{ $locale }}" class="field-label">
                                        {{ __('Accroche — :lang', ['lang' => $label]) }}
                                    </label>
                                    <textarea id="short_{{ $locale }}" name="short_description_{{ $locale }}" rows="2" maxlength="400"
                                              class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm focus:border-navy-500">{{ old('short_description_'.$locale, $property->short_description?->get($locale)) }}</textarea>
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label for="desc_{{ $locale }}" class="field-label">
                                        {{ __('Description — :lang', ['lang' => $label]) }}
                                    </label>
                                    <textarea id="desc_{{ $locale }}" name="description_{{ $locale }}" rows="9" maxlength="8000"
                                              class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm focus:border-navy-500">{{ old('description_'.$locale, $property->description?->get($locale)) }}</textarea>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-admin.panel>
            </div>

            <div x-show="tab === 'lieu'" x-cloak>
                <x-admin.panel :title="__('Localisation')"
                               :subtitle="__('Les coordonnées exactes et l\'adresse ne quittent jamais cet écran. Le public voit une position floutée à environ 500 mètres.')">
                    <div class="flex flex-col gap-5 p-5">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-ui.input name="neighborhood" :label="__('Quartier')" :value="old('neighborhood', $property->neighborhood)"
                                        placeholder="{{ __('Saly Portudal') }}" />
                            <x-ui.input name="zone" :label="__('Zone (facultatif)')" :value="old('zone', $property->zone)" />
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-ui.input name="latitude" :label="__('Latitude')" :value="old('latitude', $property->latitude)"
                                        placeholder="14.4419" />
                            <x-ui.input name="longitude" :label="__('Longitude')" :value="old('longitude', $property->longitude)"
                                        placeholder="-17.0086" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="internal_address" class="field-label">
                                {{ __('Adresse exacte — interne') }}
                            </label>
                            <textarea id="internal_address" name="internal_address" rows="2" maxlength="500"
                                      class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm focus:border-navy-500">{{ old('internal_address', $property->internal_address) }}</textarea>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="internal_notes" class="field-label">
                                {{ __('Notes internes') }}
                            </label>
                            <textarea id="internal_notes" name="internal_notes" rows="3" maxlength="4000"
                                      placeholder="{{ __('Code du portail, contact du gardien, particularités d\'accès…') }}"
                                      class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm focus:border-navy-500">{{ old('internal_notes', $property->internal_notes) }}</textarea>
                        </div>
                    </div>
                </x-admin.panel>
            </div>

            <div x-show="tab === 'equipements'" x-cloak>
                <x-admin.panel :title="__('Équipements')">
                    <div class="flex flex-col gap-6 p-5">
                        @foreach ($amenities as $category => $items)
                            <fieldset>
                                <legend class="mb-3 text-xs font-semibold uppercase tracking-wider text-navy-400">
                                    {{ $categoryLabels[$category] ?? $category }}
                                </legend>
                                <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($items as $amenity)
                                        <label class="flex cursor-pointer items-center gap-2.5 text-sm text-navy-700">
                                            <input type="checkbox" name="amenities[]" value="{{ $amenity->id }}"
                                                   @checked(in_array($amenity->id, old('amenities', $selected)))
                                                   class="size-4 rounded border-stone-400 text-navy-800 focus:ring-navy-500">
                                            <x-ui.icon :name="$amenity->icon ?? 'check'" class="size-4 text-navy-400" />
                                            {{ $amenity->name }}
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    </div>
                </x-admin.panel>
            </div>

            <div x-show="tab === 'tarifs'" x-cloak>
                <x-admin.panel :title="__('Tarifs')"
                               :subtitle="__('En francs CFA entiers. Le franc n\'a pas de centime : aucune décimale n\'est acceptée.')">
                    <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
                        <x-ui.input type="number" name="base_price" :label="__('Prix par nuit')" required min="0" step="5000"
                                    :value="old('base_price', $property->base_price?->amount)" class="no-spinner" />
                        <x-ui.input type="number" name="weekend_price" :label="__('Prix week-end')" min="0" step="5000"
                                    :value="old('weekend_price', $property->weekend_price?->amount)" class="no-spinner"
                                    :hint="__('Vendredi et samedi')" />
                        <x-ui.input type="number" name="weekly_price" :label="__('Prix semaine')" min="0" step="5000"
                                    :value="old('weekly_price', $property->weekly_price?->amount)" class="no-spinner" />
                        <x-ui.input type="number" name="cleaning_fee" :label="__('Frais de ménage')" required min="0" step="5000"
                                    :value="old('cleaning_fee', $property->cleaning_fee?->amount)" class="no-spinner" />
                        <x-ui.input type="number" name="security_deposit" :label="__('Caution')" required min="0" step="5000"
                                    :value="old('security_deposit', $property->security_deposit?->amount)" class="no-spinner" />
                    </div>

                    <p class="border-t border-stone-200 px-5 py-3 text-xs text-navy-400">
                        {{ __('Le tarif de base et le tarif week-end s\'appliquent par défaut. Les périodes de saison, ci-dessous, les remplacent quand elles couvrent la date.') }}
                    </p>
                </x-admin.panel>
            </div>

            <div x-show="tab === 'regles'" x-cloak>
                <x-admin.panel :title="__('Règles de séjour')">
                    <div class="flex flex-col gap-5 p-5">
                        <div class="grid gap-5 sm:grid-cols-4">
                            <x-ui.input type="number" name="min_nights" :label="__('Nuits minimum')" required min="1"
                                        :value="old('min_nights', $property->min_nights)" class="no-spinner" />
                            <x-ui.input type="number" name="max_nights" :label="__('Nuits maximum')" min="1"
                                        :value="old('max_nights', $property->max_nights)" class="no-spinner" />
                            <x-ui.input type="time" name="checkin_time" :label="__('Arrivée à partir de')" required
                                        :value="old('checkin_time', \Illuminate\Support\Carbon::parse($property->checkin_time)->format('H:i'))" />
                            <x-ui.input type="time" name="checkout_time" :label="__('Départ avant')" required
                                        :value="old('checkout_time', \Illuminate\Support\Carbon::parse($property->checkout_time)->format('H:i'))" />
                        </div>

                        <div class="flex flex-wrap gap-6">
                            @foreach ([
                                ['pets_allowed', __('Animaux acceptés'), $property->pets_allowed],
                                ['parties_allowed', __('Fêtes autorisées'), $property->parties_allowed],
                                ['smoking_allowed', __('Fumeur autorisé'), $property->smoking_allowed],
                            ] as [$field, $label, $current])
                                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-navy-700">
                                    <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $current))
                                           class="size-4 rounded border-stone-400 text-navy-800 focus:ring-navy-500">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </x-admin.panel>

                <x-admin.panel :title="__('Conditions de location')"
                               :subtitle="__('Visible par le voyageur avant de réserver. C\'est aussi ce que vérifie la pièce « Conditions de location » du dossier de conformité.')"
                               class="mt-5">
                    <div class="grid gap-5 p-5 lg:grid-cols-2">
                        @foreach ([['fr', __('Français')], ['en', __('Anglais')]] as [$locale, $label])
                            <div class="flex flex-col gap-1.5">
                                <label for="house_rules_{{ $locale }}" class="field-label">
                                    {{ __('Règlement intérieur — :lang', ['lang' => $label]) }}
                                </label>
                                <textarea id="house_rules_{{ $locale }}" name="house_rules_{{ $locale }}" rows="6" maxlength="4000"
                                          class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm focus:border-navy-500">{{ old('house_rules_'.$locale, $property->house_rules?->get($locale)) }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </x-admin.panel>
            </div>

            <div x-show="tab === 'seo'" x-cloak>
                <x-admin.panel :title="__('Référencement')"
                               :subtitle="__('Laissés vides, ces champs sont déduits du nom et de l\'accroche.')">
                    <div class="flex flex-col gap-5 p-5">
                        <x-ui.input name="meta_title" :label="__('Titre pour les moteurs')" :value="old('meta_title', $property->meta_title)"
                                    maxlength="180" :hint="__('60 à 70 caractères se lisent entièrement dans les résultats.')" />
                        <div class="flex flex-col gap-1.5">
                            <label for="meta_description" class="field-label">
                                {{ __('Description pour les moteurs') }}
                            </label>
                            <textarea id="meta_description" name="meta_description" rows="3" maxlength="320"
                                      class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm focus:border-navy-500">{{ old('meta_description', $property->meta_description) }}</textarea>
                        </div>
                    </div>
                </x-admin.panel>
            </div>

            <div x-show="tab !== 'photos' && tab !== 'disponibilites'" class="mt-5 flex items-center gap-3">
                <x-ui.button type="submit" size="lg">{{ __('Enregistrer') }}</x-ui.button>

                {{-- Le bouton vit ici, le formulaire plus bas : un formulaire
                     ne peut pas en contenir un autre. --}}
                <x-ui.button type="submit" form="delete-property" variant="ghost"
                             class="text-danger-700 hover:bg-danger-50">
                    {{ __('Supprimer') }}
                </x-ui.button>
            </div>
        </form>

        {{-- ------------------------------------------------ Tarifs de saison --}}
        {{-- Hors du formulaire principal : chaque règle a sa propre écriture,
             indépendante de l'enregistrement des tarifs de base ci-dessus. --}}
        <div x-show="tab === 'tarifs'" x-cloak class="mt-5">
            <x-admin.panel :title="__('Tarifs de saison')"
                           :subtitle="__('Le chevauchement entre périodes est permis — haute saison et fêtes de fin d\'année se recouvrent légitimement. C\'est la priorité qui départage : la plus haute l\'emporte.')">
                @if ($property->pricingRules->isNotEmpty())
                    <ul class="divide-y divide-stone-100">
                        @foreach ($property->pricingRules as $rule)
                            <li x-data="{ editing: false }">
                                <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                                    <div class="min-w-0">
                                        <p class="flex items-center gap-2 font-medium text-navy-900">
                                            {{ $rule->label }}
                                            <span class="text-xs font-normal text-navy-400">
                                                {{ __('priorité :n', ['n' => $rule->priority]) }}
                                            </span>
                                        </p>
                                        <p class="mt-0.5 text-sm text-navy-500 tabular">
                                            {{ $rule->starts_on->format('d/m/Y') }} → {{ $rule->ends_on->format('d/m/Y') }}
                                            <span class="text-navy-400">·</span>
                                            {{ $rule->price_per_night->format() }}
                                            @if ($rule->min_nights)
                                                <span class="text-navy-400">·</span>
                                                {{ trans_choice(':count nuit minimum|:count nuits minimum', $rule->min_nights, ['count' => $rule->min_nights]) }}
                                            @endif
                                        </p>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-1.5">
                                        <x-ui.button type="button" @click="editing = ! editing" variant="ghost" size="sm">
                                            {{ __('Modifier') }}
                                        </x-ui.button>
                                        <form method="POST" action="{{ route('admin.villas.pricing.destroy', [$property, $rule]) }}"
                                              onsubmit="return confirm('{{ __('Supprimer ce tarif de saison ?') }}')">
                                            @csrf @method('DELETE')
                                            <x-ui.button type="submit" variant="ghost" size="sm" class="text-danger-700 hover:bg-danger-50">
                                                {{ __('Supprimer') }}
                                            </x-ui.button>
                                        </form>
                                    </div>
                                </div>

                                <form x-show="editing" x-cloak method="POST"
                                      action="{{ route('admin.villas.pricing.update', [$property, $rule]) }}"
                                      class="grid gap-3 border-t border-stone-100 bg-stone-50 p-4 sm:grid-cols-2 lg:grid-cols-6">
                                    @csrf @method('PUT')
                                    <div class="sm:col-span-2 lg:col-span-2">
                                        <x-ui.input name="label" :label="__('Libellé')" required :value="$rule->label" />
                                    </div>
                                    <x-ui.input type="date" name="starts_on" :label="__('Du')" required :value="$rule->starts_on->toDateString()" />
                                    <x-ui.input type="date" name="ends_on" :label="__('Au')" required :value="$rule->ends_on->toDateString()" />
                                    <x-ui.input type="number" name="price_per_night" :label="__('Prix / nuit')" required min="0" step="5000"
                                                :value="$rule->price_per_night->amount" class="no-spinner" />
                                    <x-ui.input type="number" name="priority" :label="__('Priorité')" required min="0" max="100"
                                                :value="$rule->priority" class="no-spinner" />
                                    <div class="sm:col-span-2 lg:col-span-6 flex items-center gap-2">
                                        <x-ui.button type="submit" size="sm">{{ __('Enregistrer ce tarif') }}</x-ui.button>
                                    </div>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="px-5 py-8 text-center text-sm text-navy-400">
                        {{ __('Aucun tarif de saison. Le tarif de base et le tarif week-end s\'appliquent seuls.') }}
                    </p>
                @endif

                <form method="POST" action="{{ route('admin.villas.pricing.store', $property) }}"
                      class="grid gap-3 border-t border-stone-200 p-5 sm:grid-cols-2 lg:grid-cols-6">
                    @csrf
                    <div class="sm:col-span-2 lg:col-span-2">
                        <x-ui.input name="label" :label="__('Libellé')" required placeholder="{{ __('Haute saison') }}" />
                    </div>
                    <x-ui.input type="date" name="starts_on" :label="__('Du')" required />
                    <x-ui.input type="date" name="ends_on" :label="__('Au')" required />
                    <x-ui.input type="number" name="price_per_night" :label="__('Prix / nuit')" required min="0" step="5000" class="no-spinner" />
                    <x-ui.input type="number" name="priority" :label="__('Priorité')" required min="0" max="100" value="0" class="no-spinner" />
                    <div class="sm:col-span-2 lg:col-span-6">
                        <x-ui.button type="submit" size="sm" icon="plus">{{ __('Ajouter ce tarif') }}</x-ui.button>
                    </div>
                </form>
            </x-admin.panel>
        </div>

        {{-- ------------------------------------------------ Disponibilités --}}
        <div x-show="tab === 'disponibilites'" x-cloak class="flex flex-col gap-5">
            <x-admin.panel :title="__('Calendrier')"
                           :subtitle="__('Le jour du départ d\'un blocage reste réservable : un séjour se termine le matin, le suivant commence l\'après-midi.')">
                <div class="p-5">
                    <x-availability-calendar :property="$property" :months="3" />
                </div>
            </x-admin.panel>

            <x-admin.panel :title="__('Bloquer des dates')"
                           :subtitle="__('Entretien, indisponibilité, réserve pour le propriétaire. Un blocage ne peut pas chevaucher une réservation en cours : la base l\'interdit.')">
                @if (session('error'))
                    <div class="border-b border-stone-200 p-5 pb-0">
                        <x-ui.alert variant="danger">{{ session('error') }}</x-ui.alert>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.villas.blocks.store', $property) }}"
                      class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-5">
                    @csrf
                    <x-ui.input type="date" name="starts_on" :label="__('Du')" required :value="old('starts_on')" />
                    <x-ui.input type="date" name="ends_on" :label="__('Au')" required :value="old('ends_on')" />
                    <x-ui.select name="reason" :label="__('Motif')">
                        <option value="manual" @selected(old('reason', 'manual') === 'manual')>{{ __('Blocage manuel') }}</option>
                        <option value="maintenance" @selected(old('reason') === 'maintenance')>{{ __('Maintenance') }}</option>
                    </x-ui.select>
                    <div class="lg:col-span-2">
                        <x-ui.input name="note" :label="__('Note (facultatif)')" :value="old('note')"
                                    placeholder="{{ __('Entretien de la piscine') }}" />
                    </div>
                    <div class="sm:col-span-2 lg:col-span-5">
                        <x-ui.button type="submit" size="sm" icon="plus">{{ __('Bloquer cette période') }}</x-ui.button>
                    </div>
                </form>

                @if ($property->availabilityBlocks->isNotEmpty())
                    <x-admin.table :headers="[__('Période'), __('Motif'), __('Note'), '']">
                        @foreach ($property->availabilityBlocks as $block)
                            <tr>
                                <td class="px-4 py-3 text-navy-900 tabular">
                                    {{ $block->starts_on->format('d/m/Y') }} → {{ $block->ends_on->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    <x-ui.badge :variant="$block->reason->value === 'booking' ? 'navy' : 'warning'">
                                        {{ $block->reason->label() }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-4 py-3 text-navy-500">{{ $block->note ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($block->reason->value === 'booking')
                                        <span class="text-xs text-navy-400">
                                            {{ __('Lié à une réservation') }}
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('admin.villas.blocks.destroy', [$property, $block]) }}"
                                              onsubmit="return confirm('{{ __('Libérer ces dates ?') }}')">
                                            @csrf @method('DELETE')
                                            <x-ui.button type="submit" variant="ghost" size="sm" class="text-danger-700 hover:bg-danger-50">
                                                {{ __('Libérer') }}
                                            </x-ui.button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-admin.table>
                @else
                    <p class="border-t border-stone-200 px-5 py-8 text-center text-sm text-navy-400">
                        {{ __('Aucun blocage. Le calendrier est entièrement ouvert à la réservation.') }}
                    </p>
                @endif
            </x-admin.panel>
        </div>

        <form id="delete-property" method="POST" action="{{ route('admin.villas.destroy', $property) }}"
              onsubmit="return confirm('{{ __('Supprimer cette villa ? Elle disparaîtra du catalogue.') }}')">
            @csrf @method('DELETE')
        </form>
    </div>
</x-layouts.admin>
