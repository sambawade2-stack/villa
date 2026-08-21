<x-layouts.auth :title="__('Mot de passe oublié — Petite Côte Villas')" :heading="__('Mot de passe oublié')"
                :intro="__('Indiquez votre adresse e-mail : nous vous envoyons un lien pour en choisir un nouveau.')">

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-5">{{ session('status') }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4">
        @csrf

        <x-ui.input type="email" name="email" :label="__('Adresse e-mail')" icon="mail"
                    :value="old('email')" required autofocus autocomplete="email" />

        <x-ui.button type="submit" size="lg" class="mt-2 w-full">{{ __('Envoyer le lien') }}</x-ui.button>
    </form>

    <x-slot:footer>
        <a href="{{ route('login') }}" class="font-medium text-navy-900 underline underline-offset-4">
            {{ __('Retour à la connexion') }}
        </a>
    </x-slot:footer>
</x-layouts.auth>
