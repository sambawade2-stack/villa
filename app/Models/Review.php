<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReviewStatus;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $booking_id
 * @property int $property_id
 * @property int $user_id
 * @property int $cleanliness
 * @property int $location
 * @property int $communication
 * @property int $amenities
 * @property int $value_for_money
 * @property string $overall
 * @property string|null $comment
 * @property ReviewStatus $status
 * @property Carbon|null $published_at
 * @property string|null $admin_reply
 * @property Carbon|null $replied_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Booking|null $booking
 * @property-read Property|null $property
 * @property-read User|null $user
 */
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    /** Les cinq critères notés, dans l'ordre d'affichage de la fiche villa. */
    public const CRITERIA = [
        'cleanliness', 'location', 'communication', 'amenities', 'value_for_money',
    ];

    protected $fillable = [
        'booking_id', 'property_id', 'user_id',
        ...self::CRITERIA,
        'overall', 'comment', 'status', 'published_at', 'admin_reply', 'replied_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReviewStatus::class,
            'overall' => 'decimal:2',
            'published_at' => 'datetime',
            'replied_at' => 'datetime',
        ];
    }

    /**
     * Moyenne des cinq critères, arrondie au centième.
     *
     * @param  array<string, int|string>  $scores
     */
    public static function computeOverall(array $scores): float
    {
        $values = array_map(fn (string $c) => (int) ($scores[$c] ?? 0), self::CRITERIA);

        return round(array_sum($values) / count(self::CRITERIA), 2);
    }

    protected static function booted(): void
    {
        // Toute écriture ou suppression d'avis rafraîchit les agrégats de la villa.
        static::saved(fn (self $review) => $review->property?->recalculateRating());
        static::deleted(fn (self $review) => $review->property?->recalculateRating());
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::Approved)->whereNotNull('published_at');
    }

    public function scopePendingModeration(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::Pending);
    }
}
