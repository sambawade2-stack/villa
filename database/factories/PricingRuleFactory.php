<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PricingRule;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends Factory<PricingRule> */
class PricingRuleFactory extends Factory
{
    public function definition(): array
    {
        $start = Carbon::parse(fake()->dateTimeBetween('now', '+8 months'))->startOfDay();

        return [
            'property_id' => Property::factory(),
            'label' => fake()->randomElement(['Haute saison', 'Basse saison', 'Fêtes de fin d\'année', 'Pâques']),
            'starts_on' => $start->toDateString(),
            'ends_on' => $start->copy()->addDays(fake()->numberBetween(7, 60))->toDateString(),
            'price_per_night' => fake()->numberBetween(10, 60) * 10_000,
            'priority' => fake()->numberBetween(0, 10),
        ];
    }
}
