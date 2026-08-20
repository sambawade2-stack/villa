@props(['icon' => 'search', 'title'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-3 rounded-2xl border border-dashed border-stone-300 bg-white/60 px-6 py-16 text-center']) }}>
    <span class="flex size-12 items-center justify-center rounded-full bg-stone-100 text-navy-400">
        <x-ui.icon :name="$icon" class="size-6" />
    </span>
    <h3 class="text-lg text-navy-900">{{ $title }}</h3>
    @if (trim($slot) !== '')
        <p class="max-w-md text-sm text-navy-500">{{ $slot }}</p>
    @endif
    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @endisset
</div>
