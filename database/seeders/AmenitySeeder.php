<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    /**
     * `filterable` marque les équipements proposés comme filtres
     * sur la page de recherche — les autres n'apparaissent que sur la fiche.
     */
    public function run(): void
    {
        $amenities = [
            // slug, fr, en, catégorie, icône, filtrable
            ['piscine', 'Piscine', 'Swimming pool', 'exterieur', 'waves', true],
            ['vue-mer', 'Vue mer', 'Sea view', 'exterieur', 'eye', true],
            ['acces-plage', 'Accès plage', 'Beach access', 'exterieur', 'umbrella', true],
            ['jardin', 'Jardin', 'Garden', 'exterieur', 'tree', false],
            ['terrasse', 'Terrasse', 'Terrace', 'exterieur', 'sun', false],
            ['barbecue', 'Barbecue', 'Barbecue', 'exterieur', 'flame', false],

            ['climatisation', 'Climatisation', 'Air conditioning', 'confort', 'snowflake', true],
            ['wifi', 'Wi-Fi', 'Wi-Fi', 'confort', 'wifi', true],
            ['television', 'Télévision', 'Television', 'confort', 'tv', false],
            ['ventilateur', 'Ventilateurs', 'Ceiling fans', 'confort', 'fan', false],

            ['cuisine-equipee', 'Cuisine équipée', 'Equipped kitchen', 'cuisine', 'chef-hat', false],
            ['lave-linge', 'Lave-linge', 'Washing machine', 'cuisine', 'washing-machine', false],
            ['lave-vaisselle', 'Lave-vaisselle', 'Dishwasher', 'cuisine', 'dishwasher', false],

            ['personnel-maison', 'Personnel de maison', 'Household staff', 'services', 'users', true],
            ['gardien', 'Gardiennage', 'Caretaker', 'services', 'shield', false],
            ['menage-inclus', 'Ménage inclus', 'Housekeeping included', 'services', 'sparkles', false],

            ['parking', 'Parking privé', 'Private parking', 'pratique', 'car', true],
            ['groupe-electrogene', 'Groupe électrogène', 'Backup generator', 'pratique', 'zap', false],
            ['lit-bebe', 'Lit bébé', 'Cot available', 'pratique', 'baby', false],
            ['acces-pmr', 'Accès PMR', 'Step-free access', 'pratique', 'accessibility', false],
        ];

        foreach ($amenities as $i => [$slug, $fr, $en, $category, $icon, $filterable]) {
            Amenity::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => ['fr' => $fr, 'en' => $en],
                    'category' => $category,
                    'icon' => $icon,
                    'position' => $i,
                    'is_active' => true,
                    'is_filterable' => $filterable,
                ]
            );
        }

        $this->note('  '.count($amenities).' équipements');
    }

    /** La sortie console n'existe que si le seeder est lancé par artisan. */
    private function note(string $message): void
    {
        if (isset($this->command)) {
            $this->command->info($message);
        }
    }
}
