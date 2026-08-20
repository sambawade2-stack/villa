<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Models\Destination;
use App\Models\Property;
use App\Models\PropertyOwner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Property> */
class PropertyFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Villa '.fake()->unique()->randomElement([
            'Teranga', 'Baobab', 'Kaïra', 'Maya', 'Détente', 'Joal', 'Fajar',
            'Yeggo', 'Ngalam', 'Sopé', 'Diamono', 'Xewel', 'Salam', 'Jamm',
            'Coumba', 'Ndiaye', 'Sine', 'Saloum', 'Almadies', 'Ranérou',
        ]).' '.fake()->unique()->numberBetween(1, 999);

        // Prix ronds, en francs CFA entiers : le XOF n'a pas de sous-unité.
        $base = fake()->numberBetween(8, 50) * 10_000;
        $bedrooms = fake()->numberBetween(2, 6);

        return [
            'property_owner_id' => PropertyOwner::factory(),
            'destination_id' => Destination::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => [
                'fr' => fake()->paragraphs(3, true),
                'en' => fake()->paragraphs(3, true),
            ],
            'short_description' => [
                'fr' => fake()->sentence(14),
                'en' => fake()->sentence(14),
            ],
            'type' => PropertyType::Villa,
            'status' => PropertyStatus::Draft,
            'is_verified' => false,
            'capacity' => $bedrooms * 2,
            'bedrooms' => $bedrooms,
            'beds' => $bedrooms + fake()->numberBetween(0, 2),
            'bathrooms' => max(1, (int) ceil($bedrooms / 2)),
            'surface_sqm' => fake()->numberBetween(120, 600),
            'neighborhood' => fake()->randomElement(['Saly Portudal', 'Nianing', 'Warang', 'Ngaparou centre', 'La Somone']),
            'latitude' => fake()->latitude(14.4, 14.5),
            'longitude' => fake()->longitude(-17.05, -16.9),
            'base_price' => $base,
            'weekend_price' => (int) ($base * 1.2),
            'weekly_price' => $base * 6,
            'cleaning_fee' => fake()->numberBetween(1, 5) * 5_000,
            'security_deposit' => fake()->numberBetween(10, 30) * 10_000,
            'min_nights' => fake()->numberBetween(1, 3),
            'checkin_time' => '15:00',
            'checkout_time' => '11:00',
            'pets_allowed' => fake()->boolean(20),
            'parties_allowed' => false,
            'smoking_allowed' => false,
        ];
    }

    /**
     * Une villa publiée reçoit ses coordonnées floutées : la position exacte
     * ne quitte jamais l'administration.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PropertyStatus::Published,
            'published_at' => now()->subDays(fake()->numberBetween(1, 200)),
            'is_verified' => true,
            'approx_latitude' => round((float) $attributes['latitude'] + fake()->randomFloat(4, -0.004, 0.004), 7),
            'approx_longitude' => round((float) $attributes['longitude'] + fake()->randomFloat(4, -0.004, 0.004), 7),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => PropertyStatus::Draft, 'published_at' => null]);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }
}
