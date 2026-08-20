<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Destination;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Destination> */
class DestinationFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'name' => ['fr' => $name, 'en' => $name],
            'slug' => Str::slug($name),
            'description' => ['fr' => fake()->paragraph(), 'en' => fake()->paragraph()],
            'region' => 'Thiès',
            'latitude' => fake()->latitude(14.0, 14.8),
            'longitude' => fake()->longitude(-17.3, -16.8),
            'position' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }
}
