<x-layouts.public :title="__('Mes réservations — Petite Côte Villas')">
    <div class="border-b border-stone-200 bg-stone-50">
        <div class="container-page py-9">
            <x-ui.breadcrumb :items="[['label' => __('Accueil'), 'url' => route('home')], ['label' => __('Mes réservations')]]" />
            <h1 class="mt-3 text-3xl text-navy-900 lg:text-display">{{ __('Mes réservations') }}</h1>
        </div>
    </div>

    <div class="container-page py-9">
        @if (session('status')) <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert> @endif

        @if ($bookings->isEmpty())
            <x-ui.empty-state icon="calendar" :title="__('Aucune réservation')">
                {{ __('Vos séjours passés et à venir apparaîtront ici.') }}
                <x-slot:action>
                    <x-ui.button :href="route('villas.index')">{{ __('Trouver une villa') }}</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        @else
            <div class="flex flex-col gap-4">
                @foreach ($bookings as $booking)
                    <article class="card-hover flex flex-col gap-4 rounded-card border border-stone-200 bg-white p-4 sm:flex-row sm:items-center">
                        <span class="h-28 w-full shrink-0 overflow-hidden rounded-lg bg-stone-100 sm:size-24">
                            @if ($booking->property->primaryImage)
                                <img src="{{ $booking->property->primaryImage->url('thumb') }}" alt=""
                                     class="size-full object-cover" loading="lazy">
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-semibold text-navy-900">{{ $booking->property->name }}</h2>
                                <x-ui.badge :variant="$booking->status->color()">{{ $booking->status->label() }}</x-ui.badge>
                            </div>
                            <p class="mt-0.5 text-sm text-navy-500">{{ $booking->property->destination?->name }}</p>
                            <p class="mt-1 text-sm text-navy-600 tabular">
                                {{ $booking->checkin_date->format('d/m/Y') }} → {{ $booking->checkout_date->format('d/m/Y') }}
                                <span class="text-navy-400">·
                                    {{ trans_choice(':count nuit|:count nuits', $booking->nights, ['count' => $booking->nights]) }}
                                </span>
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-4 sm:flex-col sm:items-end">
                            <p class="font-semibold text-navy-900 tabular">{{ $booking->total_amount->format() }}</p>
                            <x-ui.button :href="route('bookings.show', $booking)" variant="outline" size="sm">
                                {{ $booking->isPending() ? __('Finaliser') : __('Voir le détail') }}
                            </x-ui.button>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">{{ $bookings->links() }}</div>
        @endif
    </div>
</x-layouts.public>
