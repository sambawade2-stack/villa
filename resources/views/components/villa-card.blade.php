@props(['property', 'eager' => false])

@php
    $image = $property->primaryImage ?? $property->images->first();
@endphp

<article {{ $attributes->merge(['class' => 'group flex flex-col']) }}>
    <div class="relative">
        <a href="{{ route('villas.show', [$property->destination, $property]) }}"
           class="card-hover block aspect-4/3 overflow-hidden rounded-card border border-transparent bg-stone-100">
            @if ($image)
                <img src="{{ $image->url('card') }}"
                     @if ($image->srcset('thumb', 'card', 'hero')) srcset="{{ $image->srcset('thumb', 'card', 'hero') }}" @endif
                     sizes="(min-width: 1280px) 25vw, (min-width: 640px) 45vw, 92vw"
                     alt="{{ $image->alt?->get() ?? $property->name }}"
                     width="{{ $image->width }}" height="{{ $image->height }}"
                     loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async"
                     class="size-full object-cover transition-transform duration-[700ms] ease-[cubic-bezier(0.22,0.61,0.36,1)] group-hover:scale-[1.05]">
            @else
                <span class="flex size-full items-center justify-center text-stone-400">
                    <x-ui.icon name="home" class="size-8" />
                </span>
            @endif
        </a>

        <x-favorite-button :property="$property" class="absolute right-2.5 top-2.5" />

        @if ($property->is_verified)
            <span class="absolute bottom-2.5 left-2.5 inline-flex items-center gap-1 rounded-md bg-navy-950/70 px-2 py-1 text-[0.7rem] font-medium text-white backdrop-blur-sm">
                <x-ui.icon name="badge-check" class="size-3.5" />
                {{ __('Villa vérifiée') }}
            </span>
        @endif
    </div>

    <div class="mt-3 flex flex-col gap-0.5">
        <h3 class="text-base font-semibold leading-snug text-navy-900">
            <a href="{{ route('villas.show', [$property->destination, $property]) }}" class="hover:underline underline-offset-2">
                {{ $property->name }}
            </a>
        </h3>

        <p class="text-sm text-navy-400">{{ $property->destination?->name }}</p>

        <div class="mt-1.5 flex items-baseline justify-between gap-3">
            <p class="text-navy-900">
                <span class="font-semibold tabular">{{ $property->base_price->format(withCurrency: false) }}</span>
                <span class="text-sm font-medium">FCFA</span>
                <span class="text-sm text-navy-400">{{ __('/nuit') }}</span>
            </p>
            <x-ui.rating :value="$property->rating_avg" :count="$property->reviews_count ?: null" class="shrink-0" />
        </div>

        <p class="mt-1 text-sm text-navy-400">
            {{ trans_choice(':count voyageur|:count voyageurs', $property->capacity, ['count' => $property->capacity]) }}
            <span aria-hidden="true" class="mx-1">•</span>
            {{ trans_choice(':count chambre|:count chambres', $property->bedrooms, ['count' => $property->bedrooms]) }}
        </p>
    </div>
</article>
