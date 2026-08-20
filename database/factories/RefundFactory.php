<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Refund> */
class RefundFactory extends Factory
{
    public function definition(): array
    {
        $payment = Payment::factory()->succeeded();

        return [
            'payment_id' => $payment,
            'booking_id' => fn (array $attributes) => Payment::findOrFail($attributes['payment_id'])->booking_id,
            'amount' => fake()->numberBetween(10, 200) * 10_000,
            'status' => 'pending',
            'reason' => 'Annulation dans le délai de remboursement intégral',
        ];
    }
}
