@props(['destinations' => null, 'variant' => 'hero'])

@php
    $destinations ??= \App\Models\Destination::query()->active()->ordered()->get();
    $today = now()->toDateString();

    // Le champ, dans la charte : intitulé discret au-dessus, valeur en dessous,
    // séparateurs verticaux plutôt que bordures individuelles.
    $cell = 'flex flex-col justify-center gap-0.5 px-4 py-2.5 min-w-0';
    $label = 'text-[0.7rem] font-medium text-navy-400';
    $control = 'w-full bg-transparent p-0 text-sm font-medium text-navy-900 '
             . 'border-0 focus:ring-0 focus:outline-none placeholder:text-navy-300';
@endphp

<form action="{{ route('villas.index') }}" method="GET"
      x-data="{
          checkin: @js(request('checkin')),
          checkout: @js(request('checkout')),
          get minCheckout() {
              if (! this.checkin) return @js($today);
              const d = new Date(this.checkin);
              d.setDate(d.getDate() + 1);
              return d.toISOString().slice(0, 10);
          },
      }"
      x-effect="if (checkout && checkout < minCheckout) checkout = minCheckout"
      {{ $attributes->merge(['class' => 'rounded-xl bg-white p-1.5 ' . ($variant === 'hero' ? 'shadow-panel' : 'border border-stone-200 shadow-card')]) }}>

    <div class="grid grid-cols-1 divide-y divide-stone-200 sm:grid-cols-2 sm:divide-y-0
                lg:grid-cols-[1.3fr_1fr_1fr_1.1fr_auto] lg:divide-x lg:divide-stone-200">

        <div class="{{ $cell }} relative">
            <label for="sb-destination" class="{{ $label }}">{{ __('Destination') }}</label>
            <div class="flex items-center gap-2">
                <select id="sb-destination" name="destination" class="{{ $control }} appearance-none truncate">
                    <option value="">{{ __('Toutes les destinations') }}</option>
                    @foreach ($destinations as $destination)
                        <option value="{{ $destination->slug }}" @selected(request('destination') === $destination->slug)>
                            {{ $destination->name }}
                        </option>
                    @endforeach
                </select>
                <x-ui.icon name="map-pin" class="size-4 shrink-0 text-navy-400" />
            </div>
        </div>

        <div class="{{ $cell }}">
            <label for="sb-checkin" class="{{ $label }}">{{ __('Arrivée') }}</label>
            <input id="sb-checkin" type="date" name="checkin" x-model="checkin"
                   min="{{ $today }}" value="{{ request('checkin') }}" class="{{ $control }}">
        </div>

        <div class="{{ $cell }}">
            <label for="sb-checkout" class="{{ $label }}">{{ __('Départ') }}</label>
            <input id="sb-checkout" type="date" name="checkout" x-model="checkout"
                   ::min="minCheckout" value="{{ request('checkout') }}" class="{{ $control }}">
        </div>

        <div class="{{ $cell }}">
            <label for="sb-guests" class="{{ $label }}">{{ __('Voyageurs') }}</label>
            <div class="flex items-center gap-2">
                <select id="sb-guests" name="guests" class="{{ $control }} appearance-none truncate">
                    <option value="">{{ __('Peu importe') }}</option>
                    @foreach ([1, 2, 3, 4, 5, 6, 8, 10, 12, 16] as $count)
                        <option value="{{ $count }}" @selected((int) request('guests') === $count)>
                            {{ trans_choice(':count voyageur|:count voyageurs', $count, ['count' => $count]) }}
                        </option>
                    @endforeach
                </select>
                <x-ui.icon name="chevron-down" class="size-4 shrink-0 text-navy-400" />
            </div>
        </div>

        {{-- Entre 640 et 1024 px la grille est à deux colonnes : le bouton prend
             toute la largeur plutôt que de laisser une demi-cellule vide. --}}
        <div class="p-1.5 sm:col-span-2 lg:col-span-1">
            <x-ui.button type="submit" size="lg" class="flex h-full w-full px-8">
                {{ __('Rechercher') }}
            </x-ui.button>
        </div>
    </div>
</form>
