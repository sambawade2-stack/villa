<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PromotionUsage> */
class PromotionUsageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'promotion_id' => Promotion::factory(),
            'booking_id' => Booking::factory(),
            'user_id' => User::factory(),
            'discount_amount' => fake()->numberBetween(1, 20) * 5_000,
        ];
    }
}
