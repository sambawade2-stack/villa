@props(['destination', 'count' => null, 'image' => null, 'eager' => false])

<a href="{{ route('destinations.show', $destination) }}"
   {{ $attributes->merge(['class' => 'group relative flex aspect-4/3 flex-col justify-end overflow-hidden rounded-card bg-navy-800']) }}>

    @if ($image)
        <img src="{{ $image->url('card') }}"
             @if ($image->srcset('thumb', 'card')) srcset="{{ $image->srcset('thumb', 'card') }}" @endif
             sizes="(min-width: 1024px) 16vw, (min-width: 640px) 30vw, 45vw"
             alt="" loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async"
             class="absolute inset-0 size-full object-cover transition-transform duration-500 group-hover:scale-[1.06]">
    @endif

    <div class="scrim-bottom absolute inset-0"></div>

    <div class="relative p-3.5">
        <p class="text-sm font-semibold text-white">{{ $destination->name }}</p>
        @if ($count !== null)
            <p class="text-xs text-white/75 tabular">
                {{ trans_choice(':count villa|:count villas', $count, ['count' => $count]) }}
            </p>
        @endif
    </div>
</a>
