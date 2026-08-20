@props(['title' => null, 'subtitle' => null])

<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-card border border-stone-200 bg-white']) }}>
    @if ($title || isset($actions))
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-200 px-5 py-4">
            <div>
                @if ($title) <h2 class="text-base font-semibold text-navy-900">{{ $title }}</h2> @endif
                @if ($subtitle) <p class="mt-0.5 text-sm text-navy-500">{{ $subtitle }}</p> @endif
            </div>
            @isset($actions) <div class="flex items-center gap-2">{{ $actions }}</div> @endisset
        </header>
    @endif

    {{ $slot }}
</section>
