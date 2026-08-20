<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Destination;
use Illuminate\Database\Seeder;

class DestinationSeeder extends Seeder
{
    /** Les six destinations de la Petite Côte, coordonnées réelles. */
    public function run(): void
    {
        $destinations = [
            [
                'slug' => 'saly',
                'fr' => 'Saly',
                'en' => 'Saly',
                'lat' => 14.4419, 'lng' => -17.0086,
                'position' => 1, 'featured' => true,
                'desc_fr' => "Première station balnéaire du Sénégal, Saly aligne plages de sable fin, golf, marché artisanal et une vie nocturne animée. C'est la porte d'entrée naturelle de la Petite Côte.",
                'desc_en' => "Senegal's foremost seaside resort, Saly combines fine sandy beaches, a golf course, a craft market and a lively nightlife. It is the natural gateway to the Petite Côte.",
            ],
            [
                'slug' => 'mbour',
                'fr' => 'Mbour',
                'en' => 'Mbour',
                'lat' => 14.4198, 'lng' => -16.9646,
                'position' => 2, 'featured' => true,
                'desc_fr' => 'Grande ville de pêche à quelques minutes de Saly, Mbour vit au rythme du retour des pirogues. Marché aux poissons, quartiers animés et accès direct à la côte.',
                'desc_en' => 'A major fishing town minutes from Saly, Mbour lives to the rhythm of the returning pirogues. Fish market, busy neighbourhoods and direct access to the coast.',
            ],
            [
                'slug' => 'ngaparou',
                'fr' => 'Ngaparou',
                'en' => 'Ngaparou',
                'lat' => 14.4667, 'lng' => -17.0333,
                'position' => 3, 'featured' => true,
                'desc_fr' => 'Village discret entre Saly et Somone, Ngaparou attire ceux qui cherchent le calme sans renoncer à la proximité des services. Belles villas en retrait de la route côtière.',
                'desc_en' => 'A quiet village between Saly and Somone, Ngaparou draws those seeking calm without giving up nearby amenities. Fine villas set back from the coastal road.',
            ],
            [
                'slug' => 'somone',
                'fr' => 'Somone',
                'en' => 'Somone',
                'lat' => 14.4906, 'lng' => -17.0553,
                'position' => 4, 'featured' => true,
                'desc_fr' => "Célèbre pour sa lagune classée réserve naturelle, Somone offre un cadre rare : eaux calmes, mangrove, oiseaux migrateurs et couchers de soleil sur l'estuaire.",
                'desc_en' => 'Famous for its lagoon, a protected nature reserve, Somone offers a rare setting: calm waters, mangroves, migratory birds and sunsets over the estuary.',
            ],
            [
                'slug' => 'popenguine',
                'fr' => 'Popenguine',
                'en' => 'Popenguine',
                'lat' => 14.5500, 'lng' => -17.1167,
                'position' => 5, 'featured' => false,
                'desc_fr' => 'Adossée à ses falaises de latérite et à sa réserve naturelle, Popenguine est la plus paisible des destinations de la Petite Côte. Plage sauvage et horizon dégagé.',
                'desc_en' => 'Backed by its laterite cliffs and nature reserve, Popenguine is the most peaceful of the Petite Côte destinations. Wild beach and open horizon.',
            ],
            [
                'slug' => 'joal-fadiouth',
                'fr' => 'Joal-Fadiouth',
                'en' => 'Joal-Fadiouth',
                'lat' => 14.1667, 'lng' => -16.8333,
                'position' => 6, 'featured' => false,
                'desc_fr' => "À l'extrémité sud de la Petite Côte, Joal-Fadiouth est célèbre pour son île aux coquillages reliée par un pont de bois. Patrimoine, calme et authenticité.",
                'desc_en' => 'At the southern tip of the Petite Côte, Joal-Fadiouth is famous for its shell island linked by a wooden bridge. Heritage, quiet and authenticity.',
            ],
        ];

        foreach ($destinations as $d) {
            Destination::updateOrCreate(
                ['slug' => $d['slug']],
                [
                    'name' => ['fr' => $d['fr'], 'en' => $d['en']],
                    'description' => ['fr' => $d['desc_fr'], 'en' => $d['desc_en']],
                    'region' => 'Thiès',
                    'latitude' => $d['lat'],
                    'longitude' => $d['lng'],
                    'position' => $d['position'],
                    'is_active' => true,
                    'is_featured' => $d['featured'],
                    'meta_title' => "Location de villas à {$d['fr']} — Petite Côte Villas",
                    'meta_description' => "Découvrez nos villas sélectionnées à {$d['fr']}, sur la Petite Côte du Sénégal.",
                ]
            );
        }

        $this->note('  '.count($destinations).' destinations');
    }

    /** La sortie console n'existe que si le seeder est lancé par artisan. */
    private function note(string $message): void
    {
        if (isset($this->command)) {
            $this->command->info($message);
        }
    }
}
