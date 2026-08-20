@props(['booking'])

<dl class="divide-y divide-stone-100 text-sm">
    <div class="flex justify-between gap-4 px-5 py-3">
        <dt class="text-navy-500">
            {{ trans_choice(':count nuit|:count nuits', $booking->nights, ['count' => $booking->nights]) }}
        </dt>
        <dd class="text-navy-900 tabular">{{ $booking->nightly_subtotal->format() }}</dd>
    </div>

    @unless ($booking->cleaning_fee->isZero())
        <div class="flex justify-between gap-4 px-5 py-3">
            <dt class="text-navy-500">{{ __('Frais de ménage') }}</dt>
            <dd class="text-navy-900 tabular">{{ $booking->cleaning_fee->format() }}</dd>
        </div>
    @endunless

    <div class="flex justify-between gap-4 px-5 py-3">
        <dt class="text-navy-500">{{ __('Frais de service') }}</dt>
        <dd class="text-navy-900 tabular">{{ $booking->service_fee->format() }}</dd>
    </div>

    @unless ($booking->discount_total->isZero())
        <div class="flex justify-between gap-4 px-5 py-3 text-success-700">
            <dt>{{ __('Remise') }}</dt>
            <dd class="tabular">− {{ $booking->discount_total->format() }}</dd>
        </div>
    @endunless

    <div class="flex justify-between gap-4 bg-stone-50 px-5 py-3 font-semibold">
        <dt class="text-navy-900">{{ __('Total à régler') }}</dt>
        <dd class="text-navy-900 tabular">{{ $booking->total_amount->format() }}</dd>
    </div>

    @unless ($booking->security_deposit->isZero())
        <div class="flex justify-between gap-4 px-5 py-3 text-xs">
            <dt class="text-navy-400">{{ __('Caution, restituée après le séjour') }}</dt>
            <dd class="text-navy-500 tabular">{{ $booking->security_deposit->format() }}</dd>
        </div>
    @endunless
</dl>
