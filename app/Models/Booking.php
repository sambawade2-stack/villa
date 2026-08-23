<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\BookingStatus;
use App\Support\Money;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference
 * @property int $property_id
 * @property int $user_id
 * @property Carbon $checkin_date
 * @property Carbon $checkout_date
 * @property int $nights
 * @property int $guests_count
 * @property BookingStatus $status
 * @property Money $nightly_subtotal
 * @property Money $cleaning_fee
 * @property Money $service_fee
 * @property Money $extra_fees_total
 * @property Money $discount_total
 * @property Money $total_amount
 * @property Money $security_deposit
 * @property string $currency
 * @property array<string, mixed>|null $price_breakdown
 * @property string|null $commission_rate
 * @property Money|null $commission_amount
 * @property Money|null $owner_payout_amount
 * @property string|null $guest_note
 * @property string|null $admin_note
 * @property Carbon|null $hold_expires_at
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property int|null $cancelled_by
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string|null $period
 * @property-read Property|null $property
 * @property-read User|null $user
 * @property-read User|null $cancelledBy
 * @property-read Collection<int, BookingGuest> $guests
 * @property-read BookingGuest|null $leadGuest
 * @property-read Collection<int, Payment> $payments
 * @property-read Payment|null $payment
 * @property-read Collection<int, Refund> $refunds
 * @property-read Commission|null $commission
 * @property-read Review|null $review
 * @property-read AvailabilityBlock|null $availabilityBlock
 * @property-read Collection<int, PromotionUsage> $promotionUsages
 */
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference', 'property_id', 'user_id',
        'checkin_date', 'checkout_date', 'nights', 'guests_count',
        'nightly_subtotal', 'cleaning_fee', 'service_fee', 'extra_fees_total',
        'discount_total', 'security_deposit', 'currency',
        'price_breakdown',
        'guest_note', 'admin_note', 'hold_expires_at',
        'confirmed_at', 'cancelled_at', 'cancellation_reason', 'cancelled_by', 'completed_at',
        // 'status', 'total_amount', 'commission_rate', 'commission_amount' et
        // 'owner_payout_amount' sont volontairement absents : seul
        // BookingService les écrit, via forceCreate/forceFill — jamais une
        // requête passée telle quelle.
    ];

    /** Colonne générée par PostgreSQL : lecture seule. */
    protected $guarded = ['period'];

    protected function casts(): array
    {
        return [
            'checkin_date' => 'date',
            'checkout_date' => 'date',
            'status' => BookingStatus::class,
            'price_breakdown' => 'array',
            'commission_rate' => 'decimal:2',
            'hold_expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'nightly_subtotal' => MoneyCast::class,
            'cleaning_fee' => MoneyCast::class,
            'service_fee' => MoneyCast::class,
            'extra_fees_total' => MoneyCast::class,
            'discount_total' => MoneyCast::class,
            'total_amount' => MoneyCast::class,
            'security_deposit' => MoneyCast::class,
            'commission_amount' => MoneyCast::class,
            'owner_payout_amount' => MoneyCast::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    // ---------------------------------------------------------------- relations

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /** @return HasMany<BookingGuest, $this> */
    public function guests(): HasMany
    {
        return $this->hasMany(BookingGuest::class);
    }

    public function leadGuest(): HasOne
    {
        return $this->hasOne(BookingGuest::class)->where('is_lead', true);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Le paiement en cours ou abouti — le dernier créé. */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /** @return HasMany<Refund, $this> */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /** Le blocage de calendrier que cette réservation immobilise. */
    public function availabilityBlock(): HasOne
    {
        return $this->hasOne(AvailabilityBlock::class);
    }

    /** @return HasMany<PromotionUsage, $this> */
    public function promotionUsages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class);
    }

    // ---------------------------------------------------------------- état

    public function isPending(): bool
    {
        return $this->status === BookingStatus::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this->status === BookingStatus::Confirmed;
    }

    /** Une tenue de dates non payée dont le délai est écoulé. */
    public function holdHasExpired(): bool
    {
        return $this->isPending()
            && $this->hold_expires_at !== null
            && $this->hold_expires_at->isPast();
    }

    /** Le droit de déposer un avis s'ouvre à la fin du séjour, pas avant. */
    public function acceptsReview(): bool
    {
        return $this->status === BookingStatus::Completed && $this->review === null;
    }

    // ---------------------------------------------------------------- scopes

    public function scopeStatus(Builder $query, BookingStatus|string $status): Builder
    {
        return $query->where('status', $status instanceof BookingStatus ? $status->value : $status);
    }

    /** Les réservations qui immobilisent réellement des dates. */
    public function scopeHoldingDates(Builder $query): Builder
    {
        return $query->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed]);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('checkin_date', '>=', now()->toDateString())
            ->orderBy('checkin_date');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('checkout_date', '<', now()->toDateString())
            ->orderByDesc('checkout_date');
    }

    public function scopeExpiredHolds(Builder $query): Builder
    {
        return $query->where('status', BookingStatus::Pending)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<', now());
    }

    public function scopeOverlapping(Builder $query, string $from, string $to): Builder
    {
        return $query->whereRaw("period && daterange(?::date, ?::date, '[)')", [$from, $to]);
    }
}
