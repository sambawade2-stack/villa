<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\PaymentStatus;
use App\Support\Money;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $booking_id
 * @property string $gateway
 * @property PaymentStatus $status
 * @property Money $amount
 * @property Money $refunded_amount
 * @property string $currency
 * @property string|null $provider_reference
 * @property string|null $checkout_url
 * @property array<string, mixed>|null $payload
 * @property Carbon|null $paid_at
 * @property Carbon|null $failed_at
 * @property string|null $failure_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Booking|null $booking
 * @property-read Collection<int, PaymentTransaction> $transactions
 * @property-read Collection<int, Refund> $refunds
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_id', 'gateway', 'status', 'amount', 'refunded_amount', 'currency',
        'provider_reference', 'checkout_url', 'payload',
        'paid_at', 'failed_at', 'failure_reason',
    ];

    protected $hidden = ['payload'];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'payload' => 'array',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'amount' => MoneyCast::class,
            'refunded_amount' => MoneyCast::class,
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return HasMany<PaymentTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /** @return HasMany<Refund, $this> */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function isSettled(): bool
    {
        return $this->status->isSettled();
    }

    public function scopeSucceeded(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Succeeded);
    }

    public function scopeGateway(Builder $query, string $gateway): Builder
    {
        return $query->where('gateway', $gateway);
    }
}
