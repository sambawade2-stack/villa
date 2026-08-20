<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Conversation> */
class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject' => fake()->randomElement([
                'Question sur les dates disponibles',
                'Transfert depuis l\'aéroport',
                'Possibilité d\'arrivée tardive',
            ]),
            'status' => ConversationStatus::Open,
            'last_message_at' => now(),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => ConversationStatus::Closed]);
    }
}
