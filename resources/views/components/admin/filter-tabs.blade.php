@props(['route', 'param' => 'status', 'current' => null, 'options' => [], 'counts' => null, 'allLabel' => null])

{{-- $options : [valeur => libellé]. La clé null représente « tout ». --}}

<div class="flex flex-wrap items-center gap-1.5">
    @if ($allLabel)
        <a href="{{ route($route) }}"
           @class(['rounded-lg px-3 py-1.5 text-sm', 'bg-navy-800 text-white' => $current === null, 'text-navy-600 hover:bg-stone-100' => $current !== null])>
            {{ $allLabel }}
        </a>
    @endif

    @foreach ($options as $value => $label)
        <a href="{{ route($route, [$param => $value]) }}"
           @class(['inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm',
                   'bg-navy-800 text-white' => (string) $current === (string) $value,
                   'text-navy-600 hover:bg-stone-100' => (string) $current !== (string) $value])>
            {{ $label }}
            @if ($counts !== null)
                <span class="text-xs opacity-70 tabular">{{ $counts[$value] ?? 0 }}</span>
            @endif
        </a>
    @endforeach
</div>
