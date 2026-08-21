<x-layouts.auth :title="__('Nouveau mot de passe — Petite Côte Villas')" :heading="__('Choisir un nouveau mot de passe')">

    <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-4">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <x-ui.input type="email" name="email" :label="__('Adresse e-mail')" icon="mail"
                    :value="old('email', $email)" required autofocus autocomplete="email" />

        <x-ui.input type="password" name="password" :label="__('Nouveau mot de passe')" icon="key"
                    required autocomplete="new-password"
                    :hint="__('Au moins 8 caractères, avec des lettres et des chiffres.')" />

        <x-ui.input type="password" name="password_confirmation" :label="__('Confirmer le mot de passe')" icon="key"
                    required autocomplete="new-password" />

        <x-ui.button type="submit" size="lg" class="mt-2 w-full">{{ __('Modifier le mot de passe') }}</x-ui.button>
    </form>

    <x-slot:footer>
        <a href="{{ route('login') }}" class="font-medium text-navy-900 underline underline-offset-4">
            {{ __('Retour à la connexion') }}
        </a>
    </x-slot:footer>
</x-layouts.auth>
