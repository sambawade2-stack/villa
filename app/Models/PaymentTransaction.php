<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\TransactionType;
use App\Support\Money;
use Database\Factories\PaymentTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Trace de chaque échange avec une passerelle.
 *
 * `provider_event_id` porte un index unique par passerelle : c'est lui qui
 * empêche un webhook rejoué de confirmer deux fois la même réservation.
 *
 * @property int $id
 * @property int $payment_id
 * @property string $gateway
 * @property TransactionType $type
 * @property string|null $status
 * @property Money|null $amount
 * @property string|null $provider_reference
 * @property string|null $provider_event_id
 * @property array<string, mixed>|null $raw_payload
 * @property string|null $ip_address
 * @property Carbon|null $occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Payment|null $payment
 */
class PaymentTransaction extends Model
{
    /** @use HasFactory<PaymentTransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'payment_id', 'gateway', 'type', 'status', 'amount',
        'provider_reference', 'provider_event_id', 'raw_payload',
        'ip_address', 'occurred_at',
    ];

    protected $hidden = ['raw_payload'];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'raw_payload' => 'array',
            'occurred_at' => 'datetime',
            'amount' => MoneyCast::class,
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
