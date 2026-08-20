<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BlockReason;
use Database\Factories\AvailabilityBlockFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Période pendant laquelle une villa n'est pas réservable.
 *
 * L'insertion est protégée en base par `availability_blocks_no_overlap` :
 * deux blocages ne peuvent pas se chevaucher sur une même villa. C'est la
 * garantie anti-double-réservation, et elle ne dépend d'aucun code PHP.
 *
 * @property int $id
 * @property int $property_id
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property BlockReason $reason
 * @property int|null $booking_id
 * @property string|null $note
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $period
 * @property-read Property|null $property
 * @property-read Booking|null $booking
 * @property-read User|null $creator
 */
class AvailabilityBlock extends Model
{
    /** @use HasFactory<AvailabilityBlockFactory> */
    use HasFactory;

    protected $fillable = [
        'property_id', 'starts_on', 'ends_on', 'reason',
        'booking_id', 'note', 'created_by',
    ];

    /** Colonne générée par PostgreSQL : lecture seule. */
    protected $guarded = ['period'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'reason' => BlockReason::class,
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOverlapping(Builder $query, string $from, string $to): Builder
    {
        return $query->whereRaw("period && daterange(?::date, ?::date, '[)')", [$from, $to]);
    }

    /** Blocages posés à la main, par opposition à ceux nés d'une réservation. */
    public function scopeManual(Builder $query): Builder
    {
        return $query->whereIn('reason', [BlockReason::Manual, BlockReason::Maintenance]);
    }

    public function scopeFromBookings(Builder $query): Builder
    {
        return $query->where('reason', BlockReason::Booking);
    }
}
