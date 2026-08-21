<x-layouts.auth :title="__('Vérifiez votre e-mail — Petite Côte Villas')" :heading="__('Vérifiez votre adresse e-mail')"
                :intro="__('Un lien de confirmation vient de vous être envoyé. Ouvrez-le pour activer votre compte.')">

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-5">{{ session('status') }}</x-ui.alert>
    @endif

    <p class="text-sm leading-relaxed text-navy-500">
        {{ __('Sans cette étape, vous pouvez tout de même parcourir le site — mais réserver une villa exige une adresse vérifiée : c\'est elle qui reçoit la confirmation et les instructions de règlement.') }}
    </p>

    <form method="POST" action="{{ route('verification.send') }}" class="mt-5">
        @csrf
        <x-ui.button type="submit" size="lg" class="w-full">{{ __('Renvoyer le lien') }}</x-ui.button>
    </form>

    <x-slot:footer>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="font-medium text-navy-900 underline underline-offset-4">
                {{ __('Se déconnecter') }}
            </button>
        </form>
    </x-slot:footer>
</x-layouts.auth>
