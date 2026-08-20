<x-layouts.public
    :title="__('Destinations de la Petite Côte — Petite Côte Villas')"
    :description="__('Saly, Mbour, Ngaparou, Somone, Popenguine, Joal-Fadiouth : découvrez les destinations de la Petite Côte du Sénégal et les villas qui s\'y louent.')"
>
    <div class="border-b border-stone-200 bg-white">
        <div class="container-page py-8">
            <x-ui.breadcrumb :items="[
                ['label' => __('Accueil'), 'url' => route('home')],
                ['label' => __('Destinations')],
            ]" />
            <h1 class="mt-3 text-3xl text-navy-900 lg:text-display">{{ __('Les destinations de la Petite Côte') }}</h1>
            <p class="mt-3 max-w-2xl leading-relaxed text-navy-500">
                {{ __('Une centaine de kilomètres de côte au sud de Dakar, de la station balnéaire de Saly aux villages de pêcheurs de Joal-Fadiouth.') }}
            </p>
        </div>
    </div>

    <div class="container-page py-10">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($destinations as $destination)
                @php $image = $destination->properties->first()?->primaryImage; @endphp

                <article class="group flex flex-col overflow-hidden rounded-card border border-stone-200 bg-white">
                    <a href="{{ route('destinations.show', $destination) }}" class="relative block aspect-16/10 overflow-hidden bg-stone-200">
                        @if ($image)
                            <img src="{{ $image->url('card') }}" srcset="{{ $image->srcset('thumb', 'card') }}"
                                 sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 92vw"
                                 alt="" loading="{{ $loop->index < 3 ? 'eager' : 'lazy' }}"
                                 class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">
                        @endif
                        <div class="scrim-soft absolute inset-0"></div>
                        <div class="absolute bottom-3 left-4">
                            <p class="font-display text-xl font-semibold text-white">{{ $destination->name }}</p>
                        </div>
                    </a>

                    <div class="flex flex-1 flex-col gap-3 p-5">
                        <p class="line-clamp-3 text-sm leading-relaxed text-navy-500">
                            {{ $destination->description?->get() }}
                        </p>
                        <div class="mt-auto flex items-center justify-between pt-2">
                            <span class="text-sm text-navy-500 tabular">
                                {{ trans_choice(':count villa|:count villas', $destination->villas_count, ['count' => $destination->villas_count]) }}
                            </span>
                            <x-ui.button :href="route('destinations.show', $destination)" variant="ghost" size="sm" icon-after="arrow-right">
                                {{ __('Découvrir') }}
                            </x-ui.button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</x-layouts.public>
