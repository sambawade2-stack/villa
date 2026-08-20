<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'gateway' => 'manual',
            'status' => PaymentStatus::Pending,
            'amount' => fake()->numberBetween(50, 500) * 10_000,
            'currency' => 'XOF',
        ];
    }

    public function succeeded(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
            'provider_reference' => 'DEMO-'.fake()->unique()->numerify('##########'),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Failed,
            'failed_at' => now(),
            'failure_reason' => 'Solde insuffisant (démonstration)',
        ]);
    }
}
