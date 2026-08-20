<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends Factory<Booking> */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $checkin = Carbon::parse(fake()->dateTimeBetween('-6 months', '+4 months'))->startOfDay();
        $nights = fake()->numberBetween(2, 14);
        $checkout = $checkin->copy()->addDays($nights);

        $nightly = fake()->numberBetween(8, 50) * 10_000 * $nights;
        $cleaning = 15_000;
        $service = (int) round($nightly * 0.03);

        return [
            'reference' => static::makeReference(),
            'property_id' => Property::factory(),
            'user_id' => User::factory(),
            'checkin_date' => $checkin->toDateString(),
            'checkout_date' => $checkout->toDateString(),
            // La base vérifie que nights = checkout - checkin : on ne peut pas mentir ici.
            'nights' => $nights,
            'guests_count' => fake()->numberBetween(1, 8),
            'status' => BookingStatus::Pending,
            'nightly_subtotal' => $nightly,
            'cleaning_fee' => $cleaning,
            'service_fee' => $service,
            'total_amount' => $nightly + $cleaning + $service,
            'security_deposit' => 100_000,
            'currency' => 'XOF',
            'hold_expires_at' => now()->addMinutes(30),
        ];
    }

    public static function makeReference(): string
    {
        return 'PCV-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999_999), 6, '0', STR_PAD_LEFT);
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::Confirmed,
            'confirmed_at' => now(),
            'hold_expires_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(function () {
            $checkout = Carbon::parse(fake()->dateTimeBetween('-8 months', '-1 week'))->startOfDay();
            $nights = fake()->numberBetween(2, 10);
            $checkin = $checkout->copy()->subDays($nights);

            return [
                'status' => BookingStatus::Completed,
                'checkin_date' => $checkin->toDateString(),
                'checkout_date' => $checkout->toDateString(),
                'nights' => $nights,
                'confirmed_at' => $checkin->copy()->subDays(20),
                'completed_at' => $checkout,
                'hold_expires_at' => null,
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now()->subDays(fake()->numberBetween(1, 60)),
            'cancellation_reason' => 'Annulation à la demande du client',
            'hold_expires_at' => null,
        ]);
    }
}
