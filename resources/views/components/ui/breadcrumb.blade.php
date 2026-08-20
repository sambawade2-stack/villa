@props(['items' => []])

{{-- $items : [['label' => …, 'url' => … | null], …] ; le dernier n'est pas un lien. --}}

<nav aria-label="{{ __('Fil d\'Ariane') }}" {{ $attributes->merge(['class' => 'flex items-center gap-1.5 text-sm text-navy-500']) }}>
    @foreach ($items as $index => $item)
        @if ($index > 0)
            <x-ui.icon name="chevron-right" class="size-3.5 text-navy-300 shrink-0" />
        @endif

        @if (! empty($item['url']) && ! $loop->last)
            <a href="{{ $item['url'] }}" class="hover:text-navy-900 transition-colors">{{ $item['label'] }}</a>
        @else
            <span class="text-navy-900 font-medium" @if ($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
