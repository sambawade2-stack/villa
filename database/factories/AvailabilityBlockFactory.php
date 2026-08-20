<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BlockReason;
use App\Models\AvailabilityBlock;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends Factory<AvailabilityBlock> */
class AvailabilityBlockFactory extends Factory
{
    public function definition(): array
    {
        $start = Carbon::parse(fake()->dateTimeBetween('now', '+6 months'))->startOfDay();

        return [
            'property_id' => Property::factory(),
            'starts_on' => $start->toDateString(),
            'ends_on' => $start->copy()->addDays(fake()->numberBetween(2, 10))->toDateString(),
            'reason' => BlockReason::Manual,
            'note' => 'Indisponibilité de démonstration',
        ];
    }

    public function forBooking(int $bookingId): static
    {
        return $this->state(fn () => [
            'reason' => BlockReason::Booking,
            'booking_id' => $bookingId,
        ]);
    }
}
