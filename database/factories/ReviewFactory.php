<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Review> */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        $scores = collect(Review::CRITERIA)
            ->mapWithKeys(fn (string $c) => [$c => fake()->numberBetween(3, 5)])
            ->all();

        return [
            'booking_id' => Booking::factory()->completed(),
            'property_id' => fn (array $attributes) => Booking::find($attributes['booking_id'])?->property_id,
            'user_id' => fn (array $attributes) => Booking::find($attributes['booking_id'])?->user_id,
            ...$scores,
            'overall' => Review::computeOverall($scores),
            'comment' => fake()->paragraph(),
            'status' => ReviewStatus::Approved,
            'published_at' => now()->subDays(fake()->numberBetween(1, 90)),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => ReviewStatus::Pending,
            'published_at' => null,
        ]);
    }
}
