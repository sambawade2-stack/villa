<x-layouts.public noindex :title="__('Mon espace propriétaire — Petite Côte Villas')">
    <div class="container-page py-9">
        <x-ui.breadcrumb :items="[
            ['label' => __('Accueil'), 'url' => route('home')],
            ['label' => __('Mon espace propriétaire')],
        ]" />

        @if (session('status')) <x-ui.alert variant="success" class="mt-4">{{ session('status') }}</x-ui.alert> @endif
        @if (session('error')) <x-ui.alert variant="danger" class="mt-4">{{ session('error') }}</x-ui.alert> @endif

        <h1 class="mt-4 text-2xl text-navy-900 lg:text-display">{{ __('Mes villas') }}</h1>
        <p class="mt-1.5 text-navy-500">{{ __('Suivez l\'état de vos villas et bloquez vos propres dates.') }}</p>

        @if ($properties->isEmpty())
            <x-ui.empty-state icon="home" :title="__('Aucune villa rattachée à votre compte')" class="mt-8">
                {{ __('Contactez l\'administration si vous pensez qu\'il s\'agit d\'une erreur.') }}
            </x-ui.empty-state>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($properties as $property)
                    <a href="{{ route('owner.villas.show', $property) }}"
                       class="card-hover flex flex-col gap-3 rounded-card border border-stone-200 bg-white p-5">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="font-semibold text-navy-900">{{ $property->name }}</h2>
                            <x-ui.badge :variant="$property->status->color()">{{ $property->status->label() }}</x-ui.badge>
                        </div>
                        <p class="text-sm text-navy-400">{{ $property->destination?->name }}</p>
                        <div class="mt-1 flex items-center gap-2">
                            <x-ui.badge :variant="$property->is_verified ? 'success' : 'warning'">
                                {{ $property->is_verified ? __('Vérifiée') : __('Dossier incomplet') }}
                            </x-ui.badge>
                            @if ($property->upcoming_bookings_count > 0)
                                <span class="text-xs text-navy-500">
                                    {{ trans_choice(':count réservation à venir|:count réservations à venir', $property->upcoming_bookings_count, ['count' => $property->upcoming_bookings_count]) }}
                                </span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.public>
