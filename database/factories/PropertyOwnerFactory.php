<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OwnerStatus;
use App\Models\PropertyOwner;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PropertyOwner> */
class PropertyOwnerFactory extends Factory
{
    public function definition(): array
    {
        $phone = '+221 77 '.fake()->numerify('### ## ##');

        return [
            // En v1 aucun propriétaire n'a de compte : la colonne reste nulle.
            'user_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => $phone,
            'whatsapp' => $phone,
            'email' => fake()->unique()->safeEmail(),
            'city' => fake()->randomElement(['Dakar', 'Mbour', 'Saly', 'Thiès']),
            'internal_address' => fake()->streetAddress(),
            'internal_notes' => null,
            'status' => OwnerStatus::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => OwnerStatus::Inactive]);
    }
}
