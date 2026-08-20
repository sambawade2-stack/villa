@props(['property', 'months' => 2])

@php
    use Illuminate\Support\Carbon;

    $start = Carbon::today()->startOfMonth();
    $end = $start->copy()->addMonths($months)->endOfMonth();

    // Un seul aller-retour en base : on déplie les périodes bloquées en dates.
    $blocked = [];
    foreach ($property->availabilityBlocks()
        ->whereDate('ends_on', '>=', $start->toDateString())
        ->whereDate('starts_on', '<=', $end->toDateString())
        ->get(['starts_on', 'ends_on']) as $block) {
        // La borne haute est exclue : le jour du départ reste réservable.
        foreach (Carbon::parse($block->starts_on)->daysUntil($block->ends_on) as $day) {
            $blocked[$day->toDateString()] = true;
        }
    }

    $today = Carbon::today();
    $weekdays = [__('Lun'), __('Mar'), __('Mer'), __('Jeu'), __('Ven'), __('Sam'), __('Dim')];
@endphp

<div {{ $attributes->merge(['class' => 'grid gap-8 sm:grid-cols-2']) }}>
    @for ($m = 0; $m < $months; $m++)
        @php
            $cursor = $start->copy()->addMonths($m);
            $firstWeekday = (int) $cursor->copy()->startOfMonth()->isoWeekday();   // 1 = lundi
            $daysInMonth = $cursor->daysInMonth;
        @endphp

        <div>
            <p class="mb-3 text-center font-sans text-sm font-semibold capitalize text-navy-900">
                {{ $cursor->translatedFormat('F Y') }}
            </p>

            <table class="w-full border-separate border-spacing-y-1 text-center text-sm">
                <caption class="sr-only">
                    {{ __('Disponibilités de :villa en :month', ['villa' => $property->name, 'month' => $cursor->translatedFormat('F Y')]) }}
                </caption>
                <thead>
                    <tr>
                        @foreach ($weekdays as $day)
                            <th scope="col" class="pb-1 text-xs font-medium text-navy-400">{{ $day }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        @for ($blank = 1; $blank < $firstWeekday; $blank++)
                            <td></td>
                        @endfor

                        @for ($day = 1; $day <= $daysInMonth; $day++)
                            @php
                                $date = $cursor->copy()->setDay($day);
                                $iso = $date->toDateString();
                                $isPast = $date->lt($today);
                                $isBlocked = isset($blocked[$iso]);
                            @endphp

                            <td class="p-0.5">
                                <span
                                    @class([
                                        'flex size-8 items-center justify-center rounded-full tabular mx-auto',
                                        'text-stone-400' => $isPast,
                                        'text-navy-300 line-through' => ! $isPast && $isBlocked,
                                        'text-navy-800 bg-success-50' => ! $isPast && ! $isBlocked,
                                    ])
                                    @if (! $isPast)
                                        title="{{ $isBlocked ? __('Indisponible') : __('Disponible') }}"
                                    @endif
                                >{{ $day }}</span>
                            </td>

                            @if ((($blank ?? 1) + $day - 1) % 7 === 0 && $day < $daysInMonth)
                                </tr><tr>
                            @endif
                        @endfor
                    </tr>
                </tbody>
            </table>
        </div>
    @endfor
</div>

<div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-navy-500">
    <span class="inline-flex items-center gap-1.5">
        <span class="size-3 rounded-full bg-success-50 ring-1 ring-success-500/30"></span>{{ __('Disponible') }}
    </span>
    <span class="inline-flex items-center gap-1.5">
        <span class="size-3 rounded-full bg-stone-200"></span><span class="line-through">{{ __('Indisponible') }}</span>
    </span>
</div>
