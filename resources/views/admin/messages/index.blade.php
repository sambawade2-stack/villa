@php
    $filters = [
        null => __('Ouvertes'),
        'non-lus' => __('Non lues'),
        'whatsapp' => __('WhatsApp'),
        'fermees' => __('Fermées'),
    ];
@endphp

<x-layouts.admin :title="__('Messages')" :heading="__('Messages')">
    <div class="flex flex-wrap items-center gap-1.5">
        @foreach ($filters as $value => $label)
            <a href="{{ route('admin.messages.index', array_filter(['filter' => $value])) }}"
               @class([
                   'inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm',
                   'bg-navy-800 text-white' => $filter === $value,
                   'text-navy-600 hover:bg-stone-100' => $filter !== $value,
               ])>
                {{ $label }}
                @if ($value === 'non-lus' && $unread > 0)
                    <span class="inline-flex size-5 items-center justify-center rounded-full bg-amber-400 text-[0.65rem] font-bold text-navy-900 tabular">
                        {{ $unread }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>

    <div class="mt-5 overflow-hidden rounded-card border border-stone-200 bg-white">
        @if ($conversations->isEmpty())
            <p class="px-5 py-12 text-center text-navy-400">{{ __('Aucune conversation.') }}</p>
        @else
            <ul class="divide-y divide-stone-100">
                @foreach ($conversations as $conversation)
                    <li>
                        <a href="{{ route('admin.messages.show', $conversation) }}"
                           class="flex items-center gap-4 px-5 py-4 transition-colors hover:bg-stone-50">
                            <span @class([
                                'flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                                'bg-navy-800 text-white' => $conversation->admin_unread_count > 0,
                                'bg-stone-100 text-navy-500' => $conversation->admin_unread_count === 0,
                            ])>
                                {{ mb_strtoupper(mb_substr($conversation->user?->first_name ?? '?', 0, 1)) }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="flex items-center gap-2 truncate font-medium text-navy-900">
                                    {{ $conversation->user?->full_name ?? ('+'.$conversation->whatsapp_number) }}
                                    @if ($conversation->whatsapp_number)
                                        <x-ui.icon name="message-circle" class="size-3.5 shrink-0 text-[#1FA855]" />
                                    @endif
                                </p>
                                <p class="truncate text-sm text-navy-500">
                                    <span class="text-navy-400">{{ $conversation->subject }} —</span>
                                    {{ Str::limit($conversation->latestMessage?->body, 80) }}
                                </p>
                            </div>

                            <div class="hidden shrink-0 text-right sm:block">
                                @if ($conversation->property)
                                    <p class="text-xs text-navy-400">{{ $conversation->property->name }}</p>
                                @endif
                                <p class="text-xs text-navy-400 tabular">
                                    {{ $conversation->last_message_at?->diffForHumans(short: true) }}
                                </p>
                            </div>

                            @if ($conversation->admin_unread_count > 0)
                                <span class="inline-flex size-5 shrink-0 items-center justify-center rounded-full bg-amber-400 text-[0.65rem] font-bold text-navy-900 tabular">
                                    {{ $conversation->admin_unread_count }}
                                </span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="mt-6">{{ $conversations->links() }}</div>
</x-layouts.admin>
