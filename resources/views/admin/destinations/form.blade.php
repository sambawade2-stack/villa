<x-layouts.admin :title="$destination->exists ? __('Modifier la destination') : __('Nouvelle destination')"
                 :heading="$destination->exists ? $destination->name->get() : __('Nouvelle destination')">

    <form method="POST" action="{{ $destination->exists ? route('admin.destinations.update', $destination) : route('admin.destinations.store') }}"
          class="max-w-3xl">
        @csrf
        @if ($destination->exists) @method('PUT') @endif

        <x-admin.panel :title="__('Nom et présentation')">
            <div class="grid gap-5 p-5 sm:grid-cols-2">
                <x-ui.input name="name_fr" :label="__('Nom (français)')" required
                            :value="old('name_fr', $destination->name?->get('fr'))" />
                <x-ui.input name="name_en" :label="__('Nom (anglais)')"
                            :value="old('name_en', $destination->name?->get('en'))"
                            :placeholder="__('Laisser vide pour reprendre le nom français')" />

                <x-ui.input name="slug" :label="__('Slug (URL)')"
                            :value="old('slug', $destination->slug)"
                            :placeholder="__('Laisser vide pour le générer depuis le nom')" />
                <x-ui.input name="region" :label="__('Région')" required
                            :value="old('region', $destination->region ?? 'Thiès')" />

                <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label for="description_fr" class="field-label">{{ __('Description (français)') }}</label>
                    <textarea id="description_fr" name="description_fr" rows="4" maxlength="4000"
                              class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm focus:border-navy-500">{{ old('description_fr', $destination->description?->get('fr')) }}</textarea>
                </div>
                <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label for="description_en" class="field-label">{{ __('Description (anglais)') }}</label>
                    <textarea id="description_en" name="description_en" rows="4" maxlength="4000"
                              class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm focus:border-navy-500">{{ old('description_en', $destination->description?->get('en')) }}</textarea>
                </div>
            </div>
        </x-admin.panel>

        <x-admin.panel class="mt-5" :title="__('Localisation et affichage')">
            <div class="grid gap-5 p-5 sm:grid-cols-2">
                <x-ui.input type="number" step="any" name="latitude" :label="__('Latitude')"
                            :value="old('latitude', $destination->latitude)" placeholder="14.4419" />
                <x-ui.input type="number" step="any" name="longitude" :label="__('Longitude')"
                            :value="old('longitude', $destination->longitude)" placeholder="-17.0086" />
                <x-ui.input type="number" name="position" :label="__('Ordre d\'affichage')" required
                            :value="old('position', $destination->position ?? 0)" />

                <div class="flex items-center gap-6 sm:col-span-2">
                    <label class="flex cursor-pointer items-center gap-2.5 text-sm text-navy-700">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $destination->exists ? $destination->is_active : true))
                               class="size-4 rounded border-stone-400 text-navy-800 focus:ring-navy-500">
                        {{ __('Active (visible publiquement)') }}
                    </label>
                    <label class="flex cursor-pointer items-center gap-2.5 text-sm text-navy-700">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $destination->is_featured))
                               class="size-4 rounded border-stone-400 text-navy-800 focus:ring-navy-500">
                        {{ __('Mise en avant (page d\'accueil)') }}
                    </label>
                </div>
            </div>
        </x-admin.panel>

        <x-admin.panel class="mt-5" :title="__('Référencement (SEO)')" :subtitle="__('Optionnel — sinon généré automatiquement.')">
            <div class="grid gap-5 p-5 sm:grid-cols-2">
                <x-ui.input name="meta_title" :label="__('Titre méta')" :value="old('meta_title', $destination->meta_title)" />
                <x-ui.input name="meta_description" :label="__('Description méta')" :value="old('meta_description', $destination->meta_description)" />
            </div>
        </x-admin.panel>

        <div class="mt-5 flex items-center gap-3">
            <x-ui.button type="submit" size="lg">{{ $destination->exists ? __('Enregistrer') : __('Créer la destination') }}</x-ui.button>
            <x-ui.button :href="route('admin.destinations.index')" variant="ghost">{{ __('Annuler') }}</x-ui.button>
        </div>
    </form>
</x-layouts.admin>
