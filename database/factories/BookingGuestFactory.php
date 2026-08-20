<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingGuest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BookingGuest> */
class BookingGuestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'full_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '+221 77 '.fake()->numerify('### ## ##'),
            'is_lead' => false,
            'age_group' => 'adult',
        ];
    }

    public function lead(): static
    {
        return $this->state(fn () => ['is_lead' => true]);
    }
}
