<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PropertyImage> */
class PropertyImageFactory extends Factory
{
    public function definition(): array
    {
        $slug = fake()->uuid();

        return [
            'property_id' => Property::factory(),
            'path' => "demo/{$slug}.jpg",
            'disk' => 'properties',
            'alt' => ['fr' => 'Photo de démonstration', 'en' => 'Demo photo'],
            'position' => 0,
            'is_primary' => false,
            'width' => 2400,
            'height' => 1600,
            'size' => fake()->numberBetween(200_000, 2_000_000),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true, 'position' => 0]);
    }
}
