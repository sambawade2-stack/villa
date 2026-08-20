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
                'regles' => __('Règles'),
                'seo' => __('Référencement'),
            ] as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'bg-navy-800 text-white' : 'text-navy-600 hover:bg-stone-100'"
                        class="rounded-lg px-3.5 py-1.5 text-sm">
                    {{ $label }}
                    @if ($key === 'photos')
                        <span class="ml-1 text-xs opacity-70 tabular">{{ $property->images->count() }}</span>
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
                        {{ __('JPEG, PNG ou WebP. :width px de large minimum, :size Mo maximum par photo. Les données EXIF, qui contiennent souvent les coordonnées GPS du bien, sont supprimées à l\'envoi.', [
                            'width' => ImageService::MIN_WIDTH,
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
                                    <label for="short_{{ $locale }}" class="text-xs font-semibold uppercase tracking-wider text-navy-500">
                                        {{ __('Accroche — :lang', ['lang' => $label]) }}
                                    </label>
                                    <textarea id="short_{{ $locale }}" name="short_description_{{ $locale }}" rows="2" maxlength="400"
                                              class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm focus:border-navy-500">{{ old('short_description_'.$locale, $property->short_description?->get($locale)) }}</textarea>
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label for="desc_{{ $locale }}" class="text-xs font-semibold uppercase tracking-wider text-navy-500">
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
                            <label for="internal_address" class="text-xs font-semibold uppercase tracking-wider text-navy-500">
                                {{ __('Adresse exacte — interne') }}
                            </label>
                            <textarea id="internal_address" name="internal_address" rows="2" maxlength="500"
                                      class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm focus:border-navy-500">{{ old('internal_address', $property->internal_address) }}</textarea>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="internal_notes" class="text-xs font-semibold uppercase tracking-wider text-navy-500">
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
                        {{ __('Les tarifs de saison se règlent villa par villa dans une prochaine version. Le tarif week-end et le prix de base couvrent l\'essentiel.') }}
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
            </div>

            <div x-show="tab === 'seo'" x-cloak>
                <x-admin.panel :title="__('Référencement')"
                               :subtitle="__('Laissés vides, ces champs sont déduits du nom et de l\'accroche.')">
                    <div class="flex flex-col gap-5 p-5">
                        <x-ui.input name="meta_title" :label="__('Titre pour les moteurs')" :value="old('meta_title', $property->meta_title)"
                                    maxlength="180" :hint="__('60 à 70 caractères se lisent entièrement dans les résultats.')" />
                        <div class="flex flex-col gap-1.5">
                            <label for="meta_description" class="text-xs font-semibold uppercase tracking-wider text-navy-500">
                                {{ __('Description pour les moteurs') }}
                            </label>
                            <textarea id="meta_description" name="meta_description" rows="3" maxlength="320"
                                      class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm focus:border-navy-500">{{ old('meta_description', $property->meta_description) }}</textarea>
                        </div>
                    </div>
                </x-admin.panel>
            </div>

            <div x-show="tab !== 'photos'" class="mt-5 flex items-center gap-3">
                <x-ui.button type="submit" size="lg">{{ __('Enregistrer') }}</x-ui.button>

                {{-- Le bouton vit ici, le formulaire plus bas : un formulaire
                     ne peut pas en contenir un autre. --}}
                <x-ui.button type="submit" form="delete-property" variant="ghost"
                             class="text-danger-700 hover:bg-danger-50">
                    {{ __('Supprimer') }}
                </x-ui.button>
            </div>
        </form>

        <form id="delete-property" method="POST" action="{{ route('admin.villas.destroy', $property) }}"
              onsubmit="return confirm('{{ __('Supprimer cette villa ? Elle disparaîtra du catalogue.') }}')">
            @csrf @method('DELETE')
        </form>
    </div>
</x-layouts.admin>
