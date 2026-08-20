<x-layouts.public :title="__('Mes favoris — Petite Côte Villas')">
    <div class="border-b border-stone-200 bg-stone-50">
        <div class="container-page py-10">
            <x-ui.breadcrumb :items="[['label' => __('Accueil'), 'url' => route('home')], ['label' => __('Mes favoris')]]" />
            <h1 class="mt-3 text-3xl text-navy-900 lg:text-display">{{ __('Mes favoris') }}</h1>
            <p class="mt-1.5 text-navy-500 tabular">
                {{ trans_choice(':count villa enregistrée|:count villas enregistrées', $properties->total(), ['count' => $properties->total()]) }}
            </p>
        </div>
    </div>

    <div class="container-page py-10">
        @if (session('status'))
            <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
        @endif

        @if ($properties->isEmpty())
            <x-ui.empty-state icon="heart" :title="__('Aucune villa enregistrée')">
                {{ __('Touchez le cœur sur une villa pour la retrouver ici.') }}
                <x-slot:action>
                    <x-ui.button :href="route('villas.index')">{{ __('Parcourir les villas') }}</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        @else
            <div class="grid gap-x-6 gap-y-9 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($properties as $property)
                    <x-villa-card :property="$property" :eager="$loop->index < 4"
                    class="reveal" :data-delay="min($loop->index, 3)" />
                @endforeach
            </div>

            <div class="mt-10">{{ $properties->onEachSide(1)->links() }}</div>
        @endif
    </div>
</x-layouts.public>
