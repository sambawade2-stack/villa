<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PromotionType;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Promotion> */
class PromotionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(8)),
            'label' => ['fr' => 'Offre de démonstration', 'en' => 'Demo offer'],
            'type' => PromotionType::Percentage,
            'value' => fake()->randomElement([5, 10, 15, 20]),
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->addMonths(3),
            'is_active' => true,
        ];
    }

    public function fixed(int $francs): static
    {
        return $this->state(fn () => ['type' => PromotionType::Fixed, 'value' => $francs]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subMonths(3),
            'ends_at' => now()->subWeek(),
        ]);
    }
}
