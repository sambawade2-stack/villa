<x-layouts.admin :title="__('Nouvelle villa')" :heading="__('Nouvelle villa')">
    <x-slot:actions>
        <x-ui.button :href="route('admin.villas.index')" variant="ghost" size="sm" icon="chevron-left">
            {{ __('Retour') }}
        </x-ui.button>
    </x-slot:actions>

    <form method="POST" action="{{ route('admin.villas.store') }}" class="max-w-2xl">
        @csrf

        <x-admin.panel :title="__('L\'essentiel')"
                       :subtitle="__('De quoi créer un brouillon. Le reste — description, photos, tarifs — s\'ajoute ensuite, section par section.')">
            <div class="flex flex-col gap-5 p-5">
                <x-ui.input name="name" :label="__('Nom de la villa')" required autofocus
                            :value="old('name')" placeholder="{{ __('Villa Teranga') }}" />

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-ui.select name="property_owner_id" :label="__('Propriétaire')" required>
                        <option value="">{{ __('Choisir…') }}</option>
                        @foreach ($owners as $owner)
                            <option value="{{ $owner->id }}" @selected(old('property_owner_id') == $owner->id)>
                                {{ $owner->full_name }}@if ($owner->city) — {{ $owner->city }}@endif
                            </option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.select name="destination_id" :label="__('Destination')" required>
                        <option value="">{{ __('Choisir…') }}</option>
                        @foreach ($destinations as $destination)
                            <option value="{{ $destination->id }}" @selected(old('destination_id') == $destination->id)>
                                {{ $destination->name }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>

                @if ($owners->isEmpty())
                    <x-ui.alert variant="warning">
                        {{ __('Aucun propriétaire enregistré.') }}
                        <a href="{{ route('admin.owners.create') }}" class="font-medium underline">{{ __('Créez-en un d\'abord.') }}</a>
                    </x-ui.alert>
                @endif

                <x-ui.select name="type" :label="__('Type de logement')" required>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(old('type', 'villa') === $type->value)>
                            {{ $type->label() }}
                        </option>
                    @endforeach
                </x-ui.select>

                <div class="grid gap-5 sm:grid-cols-3">
                    <x-ui.input type="number" name="capacity" :label="__('Voyageurs')" required min="1" max="50"
                                :value="old('capacity', 6)" class="no-spinner" />
                    <x-ui.input type="number" name="bedrooms" :label="__('Chambres')" required min="1" max="20"
                                :value="old('bedrooms', 3)" class="no-spinner" />
                    <x-ui.input type="number" name="bathrooms" :label="__('Salles de bain')" required min="1" max="20"
                                :value="old('bathrooms', 2)" class="no-spinner" />
                </div>
            </div>
        </x-admin.panel>

        <div class="mt-5 flex items-center gap-3">
            <x-ui.button type="submit" size="lg" :disabled="$owners->isEmpty()">
                {{ __('Créer le brouillon') }}
            </x-ui.button>
            <x-ui.button :href="route('admin.villas.index')" variant="ghost">{{ __('Annuler') }}</x-ui.button>
        </div>
    </form>
</x-layouts.admin>
