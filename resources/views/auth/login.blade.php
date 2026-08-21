<x-layouts.auth :title="__('Connexion — Petite Côte Villas')" :heading="__('Se connecter')"
                :intro="__('Accédez à vos réservations et à vos favoris.')">

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-5">{{ session('status') }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
        @csrf

        <x-ui.input type="email" name="email" :label="__('Adresse e-mail')" icon="mail"
                    :value="old('email')" required autofocus autocomplete="email" />

        <div class="flex flex-col gap-1.5">
            <x-ui.input type="password" name="password" :label="__('Mot de passe')" icon="key"
                        required autocomplete="current-password" />
            <a href="{{ route('password.request') }}" class="self-end text-xs text-navy-500 underline underline-offset-4 hover:text-navy-900">
                {{ __('Mot de passe oublié ?') }}
            </a>
        </div>

        <label class="flex items-center gap-2.5 text-sm text-navy-600">
            <input type="checkbox" name="remember" value="1"
                   class="size-4 rounded border-stone-400 text-navy-900 focus:ring-navy-500">
            {{ __('Rester connecté') }}
        </label>

        <x-ui.button type="submit" size="lg" class="mt-2 w-full">{{ __('Se connecter') }}</x-ui.button>
    </form>

    <x-slot:footer>
        {{ __('Pas encore de compte ?') }}
        <a href="{{ route('register') }}" class="font-medium text-navy-900 underline underline-offset-4">
            {{ __('Créer un compte') }}
        </a>
    </x-slot:footer>
</x-layouts.auth>
