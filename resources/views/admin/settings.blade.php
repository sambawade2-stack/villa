@php
    $groupLabels = [
        'commission' => __('Commission'),
        'pricing' => __('Tarification'),
        'booking' => __('Réservations'),
        'contact' => __('Coordonnées publiques'),
    ];

    $fieldLabels = [
        'platform.commission_rate' => __('Taux de commission de la plateforme (%)'),
        'platform.service_fee_rate' => __('Frais de service voyageur (%)'),
        'booking.hold_minutes' => __('Durée de tenue des dates, en minutes'),
        'booking.cancellation_full_refund_days' => __('Remboursement intégral au-delà de (jours)'),
        'booking.cancellation_half_refund_days' => __('Remboursement à 50 % au-delà de (jours)'),
        'contact.email' => __('E-mail de contact'),
        'contact.phone' => __('Téléphone'),
        'contact.whatsapp' => __('Numéro WhatsApp'),
    ];
@endphp

<x-layouts.admin :title="__('Paramètres')" :heading="__('Paramètres')">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-3xl">
        @csrf @method('PUT')

        @foreach ($settings as $group => $fields)
            <x-admin.panel class="mb-5" :title="$groupLabels[$group] ?? $group">
                <div class="grid gap-5 p-5 sm:grid-cols-2">
                    @foreach ($fields as $field)
                        <x-ui.input
                            :name="str_replace('.', '__', $field['key'])"
                            :label="$fieldLabels[$field['key']] ?? $field['key']"
                            :type="in_array($field['type'], ['numeric', 'integer']) ? 'number' : ($field['type'] === 'email' ? 'email' : 'text')"
                            :step="$field['type'] === 'numeric' ? '0.01' : null"
                            :value="old(str_replace('.', '__', $field['key']), $field['value'])"
                            :hint="$field['description']"
                        />
                    @endforeach
                </div>
            </x-admin.panel>
        @endforeach

        <x-ui.alert variant="info" class="mb-5">
            {{ __('Le taux de commission ne s\'applique qu\'aux réservations à venir : celles déjà confirmées gardent le taux figé au moment de leur confirmation.') }}
        </x-ui.alert>

        <x-ui.button type="submit" size="lg">{{ __('Enregistrer les paramètres') }}</x-ui.button>
    </form>
</x-layouts.admin>
