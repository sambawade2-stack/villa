<x-layouts.admin :title="__('Notifications')" :heading="__('Notifications')">
    @if ($unread > 0)
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                @csrf
                <x-ui.button type="submit" variant="outline" size="sm">{{ __('Tout marquer comme lu') }}</x-ui.button>
            </form>
        </x-slot:actions>
    @endif

    <x-admin.panel>
        @if ($notifications->isEmpty())
            <p class="px-5 py-12 text-center text-navy-400">{{ __('Aucune notification.') }}</p>
        @else
            <ul class="divide-y divide-stone-100">
                @foreach ($notifications as $notification)
                    @php $data = $notification->data; @endphp
                    <li>
                        <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}">
                            @csrf
                            <button type="submit"
                                    @class([
                                        'flex w-full items-start gap-4 px-5 py-4 text-left transition-colors hover:bg-stone-50',
                                        'bg-navy-50/60' => $notification->read_at === null,
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
        @endif
    </x-admin.panel>

    <div class="mt-6">{{ $notifications->links() }}</div>
</x-layouts.admin>
