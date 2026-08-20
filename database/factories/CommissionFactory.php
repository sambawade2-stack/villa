<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CommissionStatus;
use App\Models\Booking;
use App\Models\Commission;
use App\Models\PropertyOwner;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Commission> */
class CommissionFactory extends Factory
{
    public function definition(): array
    {
        $base = Money::from(fake()->numberBetween(50, 500) * 10_000);
        $rate = '10.00';
        $commission = $base->percentage($rate);

        return [
            'booking_id' => Booking::factory(),
            'property_owner_id' => PropertyOwner::factory(),
            'rate' => $rate,
            'base_amount' => $base->amount,
            'commission_amount' => $commission->amount,
            // La base impose commission + payout = base : on la respecte par construction.
            'owner_payout_amount' => $base->minus($commission)->amount,
            'status' => CommissionStatus::Pending,
        ];
    }

    public function settled(): static
    {
        return $this->state(fn () => [
            'status' => CommissionStatus::Settled,
            'settled_at' => now(),
        ]);
    }
}
