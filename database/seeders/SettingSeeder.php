<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['platform.commission_rate', 10.0, 'commission', 'Taux de commission de la plateforme, en pourcentage'],
            ['platform.service_fee_rate', 3.0, 'pricing', 'Frais de service appliqués au voyageur, en pourcentage'],
            ['booking.hold_minutes', 30, 'booking', "Délai de tenue des dates avant expiration d'une réservation non payée"],
            ['booking.min_advance_days', 0, 'booking', "Nombre de jours minimum entre aujourd'hui et l'arrivée"],
            ['booking.cancellation_full_refund_days', 30, 'booking', 'Remboursement intégral si annulation au-delà de ce délai'],
            ['booking.cancellation_half_refund_days', 7, 'booking', 'Remboursement à 50 % si annulation au-delà de ce délai'],
            ['payment.default_gateway', 'manual', 'payment', 'Passerelle utilisée par défaut'],
            ['payment.manual_instructions',
                "Wave ou Orange Money au +221 77 000 00 00 (Petite Côte Villas).\nVirement : IBAN communiqué sur demande.\nIndiquez la référence de votre réservation dans le motif.",
                'payment', 'Instructions de règlement affichées au client'],
            ['contact.email', 'contact@petitecotevillas.test', 'contact', 'Adresse de contact publique'],
            ['contact.phone', '+221 77 000 00 00', 'contact', 'Téléphone public (fictif)'],
            ['contact.whatsapp', '+221 77 000 00 00', 'contact', 'WhatsApp public (fictif)'],
        ];

        foreach ($settings as [$key, $value, $group, $description]) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group, 'description' => $description]
            );
        }

        Setting::flushCache();

        $this->note('  '.count($settings).' réglages');
    }

    /** La sortie console n'existe que si le seeder est lancé par artisan. */
    private function note(string $message): void
    {
        if (isset($this->command)) {
            $this->command->info($message);
        }
    }
}
