<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

/**
 * Textes des villas de démonstration.
 *
 * Les villas et les personnes sont fictives ; les destinations, quartiers et
 * repères géographiques sont réels, sans quoi le catalogue serait incohérent
 * (« Popenguine, quartier de Ngaparou ») et donc inutilisable pour juger du rendu.
 */
final class DemoContent
{
    /** Quartiers réels, par destination. @var array<string, list<string>> */
    public const NEIGHBOURHOODS = [
        'saly' => ['Saly Portudal', 'Saly Niakhniakhal', 'Saly Carrefour', 'Saly Nord'],
        'mbour' => ['Nianing', 'Warang', 'Mbour Centre', 'Route de Joal'],
        'ngaparou' => ['Ngaparou Centre', 'Ngaparou Plage', 'Route de la Somone'],
        'somone' => ['La Somone', 'Bord de lagune', 'Somone Village'],
        'popenguine' => ['Popenguine Village', 'Les Falaises', 'Guéréo'],
        'joal-fadiouth' => ['Joal Centre', 'Fadiouth', 'Ngazobil'],
    ];

    /** Noms de villas, uniques et sans numérotation. @var list<string> */
    public const NAMES = [
        'Villa Teranga', 'Villa Baobab', 'Villa Kaïra', 'Villa Maya', 'Villa Détente',
        'Villa Fajar', 'Villa Yeggo', 'Villa Ngalam', 'Villa Sopé', 'Villa Diamono',
        'Villa Xewel', 'Villa Salam', 'Villa Jamm', 'Villa Coumba', 'Villa Sine',
        'Villa Saloum', 'Villa Almadies', 'Villa Ranérou',
    ];

    /**
     * Descriptions longues, en français et en anglais.
     *
     * @return array{fr: string, en: string}
     */
    public static function description(string $name, string $destination, string $neighbourhood, int $bedrooms, int $capacity): array
    {
        $openings = [
            "Villa contemporaine de plain-pied, posée dans un quartier calme de {$neighbourhood}, à {$destination}.",
            "Maison de vacances lumineuse à {$neighbourhood}, dans un secteur résidentiel sécurisé de {$destination}.",
            "Belle villa familiale située à {$neighbourhood}, à quelques minutes du centre de {$destination}.",
            "Villa d'architecte ouverte sur son jardin, au cœur de {$neighbourhood}, à {$destination}.",
        ];

        $middles = [
            "Les {$bedrooms} chambres donnent toutes sur la terrasse et disposent de la climatisation. Le séjour ouvert sur la piscine accueille confortablement {$capacity} personnes.",
            "Vous disposez de {$bedrooms} chambres, d'une cuisine entièrement équipée et d'un grand salon prolongé par une terrasse couverte. La villa reçoit jusqu'à {$capacity} voyageurs.",
            "Organisée autour de la piscine, la maison compte {$bedrooms} chambres et deux espaces de vie, l'un à l'intérieur, l'autre sous la paillote. Capacité : {$capacity} personnes.",
        ];

        $closings = [
            'La plage est accessible à pied en une dizaine de minutes. Commerces, restaurants et marché se trouvent à proximité immédiate.',
            "Un gardien assure la surveillance du site et une équipe d'entretien passe chaque matin. Le transfert depuis l'aéroport peut être organisé sur demande.",
            "Idéale pour des vacances en famille ou entre amis, la villa reste au calme tout en gardant l'accès aux animations de la Petite Côte.",
        ];

        $enOpenings = [
            "Single-storey contemporary villa in a quiet part of {$neighbourhood}, {$destination}.",
            "Bright holiday home in {$neighbourhood}, within a secure residential area of {$destination}.",
            "Fine family villa in {$neighbourhood}, a few minutes from the centre of {$destination}.",
            "Architect-designed villa opening onto its garden, in the heart of {$neighbourhood}, {$destination}.",
        ];

        $enMiddles = [
            "All {$bedrooms} bedrooms open onto the terrace and are air-conditioned. The living room opens onto the pool and comfortably hosts {$capacity} guests.",
            "You have {$bedrooms} bedrooms, a fully equipped kitchen and a large living room extended by a covered terrace. The villa sleeps up to {$capacity}.",
            "Laid out around the pool, the house has {$bedrooms} bedrooms and two living areas, one indoors and one under the thatched shelter. Capacity: {$capacity} guests.",
        ];

        $enClosings = [
            'The beach is a ten-minute walk away. Shops, restaurants and the market are close by.',
            'A caretaker watches over the property and a housekeeping team comes every morning. Airport transfers can be arranged on request.',
            'Well suited to family or group holidays, the villa stays quiet while keeping easy access to the Petite Côte.',
        ];

        // Le nom sert de graine : le même jeu de démonstration se régénère à l'identique.
        $seed = crc32($name);
        $pick = static fn (array $set, int $offset): string => $set[($seed >> $offset) % count($set)];

        return [
            'fr' => implode("\n\n", [$pick($openings, 0), $pick($middles, 4), $pick($closings, 8)]),
            'en' => implode("\n\n", [$pick($enOpenings, 0), $pick($enMiddles, 4), $pick($enClosings, 8)]),
        ];
    }

    /** @return array{fr: string, en: string} */
    public static function shortDescription(string $destination, string $neighbourhood, int $bedrooms): array
    {
        return [
            'fr' => "Villa {$bedrooms} chambres avec piscine à {$neighbourhood}, {$destination}, à quelques minutes de la plage.",
            'en' => "{$bedrooms}-bedroom villa with pool in {$neighbourhood}, {$destination}, minutes from the beach.",
        ];
    }
}
