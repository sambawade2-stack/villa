<x-layouts.auth :title="__('Créer un compte — Petite Côte Villas')" :heading="__('Créer un compte')"
                :intro="__('Pour réserver, suivre vos séjours et enregistrer vos villas préférées.')">

    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-4">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input name="first_name" :label="__('Prénom')" :value="old('first_name')"
                        required autofocus autocomplete="given-name" />
            <x-ui.input name="last_name" :label="__('Nom')" :value="old('last_name')"
                        required autocomplete="family-name" />
        </div>

        <x-ui.input type="email" name="email" :label="__('Adresse e-mail')" icon="mail"
                    :value="old('email')" required autocomplete="email" />

        <x-ui.input type="tel" name="phone" :label="__('Téléphone')" icon="phone"
                    :value="old('phone')" placeholder="+221 77 000 00 00" autocomplete="tel" />

        <x-ui.input type="password" name="password" :label="__('Mot de passe')" icon="key"
                    required autocomplete="new-password"
                    :hint="__('Au moins 8 caractères, avec des lettres et des chiffres.')" />

        <x-ui.input type="password" name="password_confirmation" :label="__('Confirmer le mot de passe')" icon="key"
                    required autocomplete="new-password" />

        <x-ui.button type="submit" size="lg" class="mt-2 w-full">{{ __('Créer mon compte') }}</x-ui.button>
    </form>

    <x-slot:footer>
        {{ __('Vous avez déjà un compte ?') }}
        <a href="{{ route('login') }}" class="font-medium text-navy-900 underline underline-offset-4">
            {{ __('Se connecter') }}
        </a>
    </x-slot:footer>
</x-layouts.auth>
