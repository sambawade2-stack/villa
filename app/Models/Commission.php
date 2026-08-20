<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\CommissionStatus;
use App\Support\Money;
use Database\Factories\CommissionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Part de plateforme sur une réservation.
 *
 * Taux et montants sont figés à la confirmation : modifier le taux
 * plus tard ne doit jamais réécrire l'historique comptable.
 *
 * @property int $id
 * @property int $booking_id
 * @property int $property_owner_id
 * @property string $rate
 * @property Money $base_amount
 * @property Money $commission_amount
 * @property Money $owner_payout_amount
 * @property CommissionStatus $status
 * @property Carbon|null $settled_at
 * @property string|null $settlement_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Booking|null $booking
 * @property-read PropertyOwner|null $owner
 */
class Commission extends Model
{
    /** @use HasFactory<CommissionFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_id', 'property_owner_id', 'rate',
        'base_amount', 'commission_amount', 'owner_payout_amount',
        'status', 'settled_at', 'settlement_note',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'status' => CommissionStatus::class,
            'settled_at' => 'datetime',
            'base_amount' => MoneyCast::class,
            'commission_amount' => MoneyCast::class,
            'owner_payout_amount' => MoneyCast::class,
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(PropertyOwner::class, 'property_owner_id');
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->where('status', CommissionStatus::Pending);
    }
}
