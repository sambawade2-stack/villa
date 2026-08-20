<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Casts\TranslatableCast;
use App\Enums\PromotionType;
use App\Support\Money;
use App\Support\Translated;
use Database\Factories\PromotionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property Translated|null $label
 * @property PromotionType $type
 * @property string $value
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int|null $max_uses
 * @property int|null $max_uses_per_user
 * @property int $uses_count
 * @property int|null $min_nights
 * @property Money|null $min_total
 * @property int|null $destination_id
 * @property int|null $property_id
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Destination|null $destination
 * @property-read Property|null $property
 * @property-read Collection<int, PromotionUsage> $usages
 */
class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'label', 'type', 'value', 'starts_at', 'ends_at',
        'max_uses', 'max_uses_per_user', 'uses_count',
        'min_nights', 'min_total', 'destination_id', 'property_id', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'label' => TranslatableCast::class,
            'type' => PromotionType::class,
            'value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'min_total' => MoneyCast::class,
        ];
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /** @return HasMany<PromotionUsage, $this> */
    public function usages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class);
    }

    /** Remise produite sur un sous-total donné. Jamais supérieure au sous-total. */
    public function discountOn(Money $subtotal): Money
    {
        $discount = match ($this->type) {
            PromotionType::Percentage => $subtotal->percentage((string) $this->value),
            PromotionType::Fixed => Money::from((int) round((float) $this->value)),
        };

        return $discount->greaterThan($subtotal) ? $subtotal : $discount;
    }

    public function hasCapacityLeft(): bool
    {
        return $this->max_uses === null || $this->uses_count < $this->max_uses;
    }

    public function isWithinWindow(?string $date = null): bool
    {
        $moment = $date ? Carbon::parse($date) : now();

        return ($this->starts_at === null || $this->starts_at->lte($moment))
            && ($this->ends_at === null || $this->ends_at->gte($moment));
    }

    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('uses_count', '<', 'max_uses'));
    }
}
