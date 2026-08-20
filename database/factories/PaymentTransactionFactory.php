<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PaymentTransaction> */
class PaymentTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'gateway' => 'manual',
            'type' => TransactionType::Webhook,
            'status' => 'succeeded',
            'provider_event_id' => 'evt_'.Str::lower(Str::random(20)),
            'raw_payload' => ['demo' => true],
            'occurred_at' => now(),
        ];
    }
}
