@props(['property'])

@php
    $isFavorite = auth()->check() && auth()->user()->hasFavorited($property);
@endphp

@auth
    <form method="POST" action="{{ route('favorites.toggle', $property) }}" {{ $attributes }}>
        @csrf
        <button type="submit"
                class="flex size-8 items-center justify-center rounded-full bg-white/95 shadow-sm transition hover:scale-105"
                title="{{ $isFavorite ? __('Retirer des favoris') : __('Ajouter aux favoris') }}"
                aria-pressed="{{ $isFavorite ? 'true' : 'false' }}">
            <span class="sr-only">{{ $isFavorite ? __('Retirer des favoris') : __('Ajouter aux favoris') }}</span>
            <x-ui.icon name="heart"
                       class="size-4 {{ $isFavorite ? 'fill-danger-500 text-danger-500' : 'text-navy-700' }}" />
        </button>
    </form>
@else
    {{-- Sans compte, pas de favori à enregistrer : on mène à la connexion
         plutôt que d'afficher un bouton qui ne retiendrait rien. --}}
    <a href="{{ route('login') }}" {{ $attributes->merge(['class' => 'flex size-8 items-center justify-center rounded-full bg-white/95 shadow-sm transition hover:scale-105']) }}
       title="{{ __('Connectez-vous pour enregistrer cette villa') }}">
        <span class="sr-only">{{ __('Connectez-vous pour enregistrer cette villa') }}</span>
        <x-ui.icon name="heart" class="size-4 text-navy-700" />
    </a>
@endauth
