<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Support\Money;
use Database\Factories\PromotionUsageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $promotion_id
 * @property int $booking_id
 * @property int $user_id
 * @property Money $discount_amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Promotion|null $promotion
 * @property-read Booking|null $booking
 * @property-read User|null $user
 */
class PromotionUsage extends Model
{
    /** @use HasFactory<PromotionUsageFactory> */
    use HasFactory;

    protected $fillable = ['promotion_id', 'booking_id', 'user_id', 'discount_amount'];

    protected function casts(): array
    {
        return ['discount_amount' => MoneyCast::class];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
