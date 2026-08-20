@php use App\Enums\OwnerStatus; @endphp

<x-layouts.admin :title="$owner->exists ? __('Modifier le propriétaire') : __('Nouveau propriétaire')"
                 :heading="$owner->exists ? $owner->full_name : __('Nouveau propriétaire')">

    <form method="POST" action="{{ $owner->exists ? route('admin.owners.update', $owner) : route('admin.owners.store') }}"
          class="max-w-3xl">
        @csrf
        @if ($owner->exists) @method('PUT') @endif

        <x-admin.panel :title="__('Identité et contact')">
            <div class="grid gap-5 p-5 sm:grid-cols-2">
                <x-ui.input name="first_name" :label="__('Prénom')" required :value="old('first_name', $owner->first_name)" />
                <x-ui.input name="last_name" :label="__('Nom')" required :value="old('last_name', $owner->last_name)" />
                <x-ui.input name="phone" :label="__('Téléphone')" icon="phone" required
                            :value="old('phone', $owner->phone)" placeholder="+221 77 000 00 00" />
                <x-ui.input name="whatsapp" :label="__('WhatsApp')" icon="message-circle"
                            :value="old('whatsapp', $owner->whatsapp)" placeholder="+221 77 000 00 00" />
                <x-ui.input type="email" name="email" :label="__('E-mail')" icon="mail" :value="old('email', $owner->email)" />
                <x-ui.input name="city" :label="__('Ville')" :value="old('city', $owner->city)" />
            </div>
        </x-admin.panel>

        <x-admin.panel class="mt-5" :title="__('Informations internes')"
                       :subtitle="__('Jamais affichées sur le site public.')">
            <div class="flex flex-col gap-5 p-5">
                <div class="flex flex-col gap-1.5">
                    <label for="internal_address" class="text-xs font-semibold uppercase tracking-wider text-navy-500">
                        {{ __('Adresse') }}
                    </label>
                    <textarea id="internal_address" name="internal_address" rows="2" maxlength="500"
                              class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm focus:border-navy-500">{{ old('internal_address', $owner->internal_address) }}</textarea>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="internal_notes" class="text-xs font-semibold uppercase tracking-wider text-navy-500">
                        {{ __('Notes') }}
                    </label>
                    <textarea id="internal_notes" name="internal_notes" rows="4" maxlength="4000"
                              class="w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm focus:border-navy-500">{{ old('internal_notes', $owner->internal_notes) }}</textarea>
                </div>

                <x-ui.select name="status" :label="__('Statut')" class="sm:w-64">
                    @foreach (OwnerStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected(old('status', $owner->status?->value ?? 'active') === $case->value)>
                            {{ $case->label() }}
                        </option>
                    @endforeach
                </x-ui.select>
            </div>
        </x-admin.panel>

        <div class="mt-5 flex items-center gap-3">
            <x-ui.button type="submit" size="lg">{{ $owner->exists ? __('Enregistrer') : __('Créer le propriétaire') }}</x-ui.button>
            <x-ui.button :href="route('admin.owners.index')" variant="ghost">{{ __('Annuler') }}</x-ui.button>
        </div>
    </form>
</x-layouts.admin>
