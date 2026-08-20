@php $hero = $properties->first()?->primaryImage; @endphp

<x-layouts.public
    :title="$destination->meta_title ?: __('Location de villas à :name', ['name' => $destination->name])"
    :description="$destination->meta_description ?: Str::limit((string) $destination->description?->get(), 155)"
    :og-image="$hero?->url('hero')"
>
    <section class="relative isolate flex min-h-[22rem] items-end overflow-hidden bg-navy-900">
        @if ($hero)
            <img src="{{ $hero->url('hero') }}" srcset="{{ $hero->srcset('card', 'hero') }}" sizes="100vw"
                 alt="" fetchpriority="high" class="absolute inset-0 -z-10 size-full object-cover">
        @endif
        <div class="scrim absolute inset-0 -z-10"></div>

        <div class="container-page w-full pb-10 pt-24">
            <x-ui.breadcrumb class="!text-stone-200" :items="[
                ['label' => __('Accueil'), 'url' => route('home')],
                ['label' => __('Destinations'), 'url' => route('destinations.index')],
                ['label' => (string) $destination->name],
            ]" />
            <h1 class="mt-3 text-4xl text-white lg:text-hero">{{ $destination->name }}</h1>
            <p class="mt-2 text-stone-200 tabular">
                {{ trans_choice(':count villa disponible|:count villas disponibles', $properties->total(), ['count' => $properties->total()]) }}
            </p>
        </div>
    </section>

    <div class="container-page py-12">
        @if ($destination->description?->get())
            <div class="max-w-3xl">
                <p class="text-lg leading-relaxed text-navy-600">{{ $destination->description->get() }}</p>
            </div>
        @endif

        <div class="mt-10">
            @if ($properties->isEmpty())
                <x-ui.empty-state icon="home" :title="__('Aucune villa publiée à :name pour le moment', ['name' => $destination->name])">
                    {{ __('Notre sélection s\'étoffe régulièrement.') }}
                    <x-slot:action>
                        <x-ui.button :href="route('villas.index')" variant="outline">{{ __('Voir toutes les villas') }}</x-ui.button>
                    </x-slot:action>
                </x-ui.empty-state>
            @else
                <div class="grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($properties as $property)
                        <x-villa-card :property="$property" :eager="$loop->index < 3"
                            class="reveal" :data-delay="min($loop->index, 3)" />
                    @endforeach
                </div>

                <div class="mt-12">{{ $properties->onEachSide(1)->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.public>
