<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Support\Money;
use Database\Factories\PricingRuleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Tarif applicable sur une période donnée : haute saison, fêtes, promotion longue durée.
 *
 * Le chevauchement est ici volontairement permis — « haute saison » et « Noël »
 * se recouvrent légitimement. C'est `priority` qui départage.
 *
 * @property int $id
 * @property int $property_id
 * @property string $label
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property Money $price_per_night
 * @property int|null $min_nights
 * @property int $priority
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $period
 * @property-read Property|null $property
 */
class PricingRule extends Model
{
    /** @use HasFactory<PricingRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'property_id', 'label', 'starts_on', 'ends_on',
        'price_per_night', 'min_nights', 'priority',
    ];

    /** Colonne générée par PostgreSQL : lecture seule. */
    protected $guarded = ['period'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'price_per_night' => MoneyCast::class,
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /** Règles couvrant une date donnée, la plus prioritaire en tête. */
    public function scopeCovering(Builder $query, string $date): Builder
    {
        return $query
            ->whereRaw('period @> ?::date', [$date])
            ->orderByDesc('priority')
            ->orderByDesc('id');
    }

    public function scopeOverlapping(Builder $query, string $from, string $to): Builder
    {
        return $query->whereRaw("period && daterange(?::date, ?::date, '[)')", [$from, $to]);
    }
}
