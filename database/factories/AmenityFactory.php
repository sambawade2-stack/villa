<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Amenity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Amenity> */
class AmenityFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ['fr' => Str::ucfirst($name), 'en' => Str::ucfirst($name)],
            'slug' => Str::slug($name),
            'icon' => 'star',
            'category' => 'general',
            'position' => fake()->numberBetween(0, 30),
            'is_active' => true,
            'is_filterable' => false,
        ];
    }
}
