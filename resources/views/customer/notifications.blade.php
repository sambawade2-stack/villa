<x-layouts.public :title="__('Notifications — Petite Côte Villas')">
    <div class="border-b border-stone-200 bg-stone-50">
        <div class="container-page py-9">
            <x-ui.breadcrumb :items="[['label' => __('Accueil'), 'url' => route('home')], ['label' => __('Notifications')]]" />
            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-3xl text-navy-900 lg:text-display">{{ __('Notifications') }}</h1>
                @if ($unread > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <x-ui.button type="submit" variant="outline" size="sm">{{ __('Tout marquer comme lu') }}</x-ui.button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="container-page py-9">
        @if (session('status')) <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert> @endif

        @if ($notifications->isEmpty())
            <x-ui.empty-state icon="info" :title="__('Aucune notification')">
                {{ __('Vos réservations et vos messages apparaîtront ici.') }}
            </x-ui.empty-state>
        @else
            <div class="overflow-hidden rounded-card border border-stone-200 bg-white">
                <ul class="divide-y divide-stone-100">
                    @foreach ($notifications as $notification)
                        @php $data = $notification->data; @endphp
                        <li>
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                @csrf
                                <button type="submit"
                                        @class([
                                            'flex w-full items-start gap-4 px-5 py-4 text-left transition-colors hover:bg-stone-50',
                                            'bg-navy-50/50' => $notification->read_at === null,
                                        ])>
                                    <span @class([
                                        'flex size-9 shrink-0 items-center justify-center rounded-full',
                                        'bg-navy-800 text-white' => $notification->read_at === null,
                                        'bg-stone-100 text-navy-400' => $notification->read_at !== null,
                                    ])>
                                        <x-ui.icon :name="$data['icon'] ?? 'info'" class="size-4" />
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-medium text-navy-900">{{ $data['title'] ?? __('Notification') }}</span>
                                        <span class="mt-0.5 block text-sm text-navy-500">{{ $data['message'] ?? '' }}</span>
                                        @if (! empty($data['reference']))
                                            <span class="mt-1 block text-xs text-navy-400 tabular">{{ $data['reference'] }}</span>
                                        @endif
                                    </span>

                                    <span class="shrink-0 text-xs text-navy-400 tabular">
                                        {{ $notification->created_at->diffForHumans(short: true) }}
                                    </span>
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="mt-8">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-layouts.public>
