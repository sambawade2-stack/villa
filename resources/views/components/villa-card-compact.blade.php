@props(['property', 'eager' => false])

@php
    $image = $property->primaryImage ?? $property->images->first();

    // Trois équipements au plus, pour rester lisible sur une seule ligne
    // dense — la fiche détaillée montre le reste.
    $highlights = $property->amenities
        ->whereIn('slug', ['piscine', 'vue-mer', 'climatisation', 'wifi', 'acces-plage'])
        ->take(3);
@endphp

{{--
    Carte de résultat compacte, réservée à la page de recherche.
    Image à gauche, contenu à droite, hauteur de ligne fixe et courte : un
    format horizontal fait tenir bien plus de résultats sur un même écran
    qu'une carte en portrait — c'est ce qui donne à une liste de recherche
    sa densité, plutôt qu'un rendu de magazine.
--}}
<article {{ $attributes->merge(['class' => 'group flex gap-4 rounded-card border border-stone-200 bg-white p-3 transition-colors hover:border-stone-300']) }}>
    <a href="{{ route('villas.show', [$property->destination, $property]) }}"
       class="relative block h-32 w-40 shrink-0 overflow-hidden rounded-lg bg-stone-100 sm:h-36 sm:w-52">
        @if ($image)
            <img src="{{ $image->url('card') }}"
                 @if ($image->srcset('thumb', 'card')) srcset="{{ $image->srcset('thumb', 'card') }}" @endif
                 sizes="(min-width: 640px) 208px, 160px"
                 alt="{{ $image->alt?->get() ?? $property->name }}"
                 width="{{ $image->width }}" height="{{ $image->height }}"
                 loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async"
                 class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">
        @else
            <span class="flex size-full items-center justify-center text-stone-400">
                <x-ui.icon name="home" class="size-6" />
            </span>
        @endif

        @if ($property->is_verified)
            <span class="absolute bottom-1.5 left-1.5 inline-flex items-center gap-1 rounded-md bg-navy-950/70 px-1.5 py-0.5 text-[0.65rem] font-medium text-white backdrop-blur-sm">
                <x-ui.icon name="badge-check" class="size-3" />
                {{ __('Vérifiée') }}
            </span>
        @endif
    </a>

    <div class="flex min-w-0 flex-1 flex-col py-0.5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="truncate text-base font-semibold leading-snug text-navy-900">
                    <a href="{{ route('villas.show', [$property->destination, $property]) }}" class="hover:underline underline-offset-2">
                        {{ $property->name }}
                    </a>
                </h3>
                <p class="mt-0.5 flex items-center gap-1 text-sm text-navy-400">
                    <x-ui.icon name="map-pin" class="size-3.5 shrink-0" />
                    <span class="truncate">
                        {{ $property->neighborhood ? $property->neighborhood.', ' : '' }}{{ $property->destination?->name }}
                    </span>
                </p>
            </div>

            <x-favorite-button :property="$property" class="shrink-0" />
        </div>

        <p class="mt-2 flex items-center gap-x-3 gap-y-1 text-sm text-navy-500">
            <span class="inline-flex items-center gap-1">
                <x-ui.icon name="users" class="size-3.5" />{{ $property->capacity }}
            </span>
            <span class="inline-flex items-center gap-1">
                <x-ui.icon name="bed" class="size-3.5" />{{ $property->bedrooms }}
            </span>
            <span class="inline-flex items-center gap-1">
                <x-ui.icon name="bath" class="size-3.5" />{{ $property->bathrooms }}
            </span>
            @foreach ($highlights as $amenity)
                <span class="hidden items-center gap-1 sm:inline-flex">
                    <x-ui.icon :name="$amenity->icon ?? 'check'" class="size-3.5" />{{ $amenity->name }}
                </span>
            @endforeach
        </p>

        <div class="mt-auto flex items-end justify-between gap-3 pt-2">
            <x-ui.rating :value="$property->rating_avg" :count="$property->reviews_count ?: null" />

            <p class="text-right text-navy-900">
                <span class="text-lg font-bold tabular">{{ $property->base_price->format(withCurrency: false) }}</span>
                <span class="text-sm font-medium">FCFA</span>
                <span class="block text-xs text-navy-400">{{ __('par nuit') }}</span>
            </p>
        </div>
    </div>
</article>
