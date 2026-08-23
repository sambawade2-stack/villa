<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Casts\TranslatableCast;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Support\CatalogCache;
use App\Support\Money;
use App\Support\Translated;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $property_owner_id
 * @property int $destination_id
 * @property string $name
 * @property string $slug
 * @property Translated|null $description
 * @property Translated|null $short_description
 * @property PropertyType $type
 * @property PropertyStatus $status
 * @property bool $is_verified
 * @property bool $is_featured
 * @property int $capacity
 * @property int $bedrooms
 * @property int $beds
 * @property int $bathrooms
 * @property int|null $surface_sqm
 * @property string|null $neighborhood
 * @property string|null $zone
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $approx_latitude
 * @property string|null $approx_longitude
 * @property string|null $internal_address
 * @property string|null $internal_notes
 * @property Money $base_price
 * @property Money|null $weekend_price
 * @property Money|null $weekly_price
 * @property Money|null $high_season_price
 * @property Money|null $low_season_price
 * @property Money $cleaning_fee
 * @property Money $security_deposit
 * @property array<string, mixed>|null $extra_fees
 * @property int $min_nights
 * @property int|null $max_nights
 * @property string $checkin_time
 * @property string $checkout_time
 * @property bool $pets_allowed
 * @property bool $parties_allowed
 * @property bool $smoking_allowed
 * @property Translated|null $house_rules
 * @property string|null $rating_avg
 * @property int $reviews_count
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read PropertyOwner|null $owner
 * @property-read Destination|null $destination
 * @property-read Collection<int, PropertyImage> $images
 * @property-read PropertyImage|null $primaryImage
 * @property-read Collection<int, Amenity> $amenities
 * @property-read Collection<int, PricingRule> $pricingRules
 * @property-read Collection<int, AvailabilityBlock> $availabilityBlocks
 * @property-read Collection<int, Booking> $bookings
 * @property-read Collection<int, Review> $reviews
 * @property-read Collection<int, Favorite> $publishedReviews
 * @property-read Collection<int, Favorite> $favorites
 */
class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'properties';

    protected $fillable = [
        'property_owner_id', 'destination_id', 'name', 'slug',
        'description', 'short_description', 'type',
        'capacity', 'bedrooms', 'beds', 'bathrooms', 'surface_sqm',
        'neighborhood', 'zone', 'latitude', 'longitude',
        'approx_latitude', 'approx_longitude', 'internal_address', 'internal_notes',
        'base_price', 'weekend_price', 'weekly_price',
        'high_season_price', 'low_season_price',
        'cleaning_fee', 'security_deposit', 'extra_fees',
        'min_nights', 'max_nights', 'checkin_time', 'checkout_time',
        'pets_allowed', 'parties_allowed', 'smoking_allowed', 'house_rules',
        'meta_title', 'meta_description',
        // 'status', 'is_verified', 'is_featured' et 'published_at' sont
        // volontairement absents : seuls PropertyController::store/publish/
        // unpublish et ComplianceService::syncPropertyVerification les
        // écrivent, via forceFill ou forceCreate — jamais depuis une requête
        // passée telle quelle.
    ];

    /**
     * Ne jamais exposer ces champs dans une réponse JSON publique.
     * La position exacte d'une villa est une donnée privée.
     */
    protected $hidden = ['internal_address', 'internal_notes', 'latitude', 'longitude'];

    protected function casts(): array
    {
        return [
            'description' => TranslatableCast::class,
            'short_description' => TranslatableCast::class,
            'house_rules' => TranslatableCast::class,
            'type' => PropertyType::class,
            'status' => PropertyStatus::class,
            'extra_fees' => 'array',
            'is_verified' => 'boolean',
            'is_featured' => 'boolean',
            'pets_allowed' => 'boolean',
            'parties_allowed' => 'boolean',
            'smoking_allowed' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'approx_latitude' => 'decimal:7',
            'approx_longitude' => 'decimal:7',
            'rating_avg' => 'decimal:2',
            'published_at' => 'datetime',
            'base_price' => MoneyCast::class,
            'weekend_price' => MoneyCast::class,
            'weekly_price' => MoneyCast::class,
            'high_season_price' => MoneyCast::class,
            'low_season_price' => MoneyCast::class,
            'cleaning_fee' => MoneyCast::class,
            'security_deposit' => MoneyCast::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ---------------------------------------------------------------- relations

    public function owner(): BelongsTo
    {
        return $this->belongsTo(PropertyOwner::class, 'property_owner_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    /** @return HasMany<PropertyImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('position')->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(PropertyImage::class)->where('is_primary', true);
    }

    /** @return BelongsToMany<Amenity, $this> */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'property_amenity');
    }

    /** @return HasMany<PricingRule, $this> */
    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    /** @return HasMany<AvailabilityBlock, $this> */
    public function availabilityBlocks(): HasMany
    {
        return $this->hasMany(AvailabilityBlock::class);
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function publishedReviews(): HasMany
    {
        return $this->reviews()->approved();
    }

    /**
     * Dossier de conformité.
     *
     * Volontairement absent de toute sérialisation publique : les pièces
     * référencées sont des documents d'identité et des titres de propriété.
     *
     * @return HasMany<ComplianceCheck, $this>
     */
    public function complianceChecks(): HasMany
    {
        return $this->hasMany(ComplianceCheck::class);
    }

    /** @return HasMany<Favorite, $this> */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    protected static function booted(): void
    {
        /*
         * Le nombre de villas par destination est affiché sur l'accueil et mis
         * en cache : publier, dépublier ou supprimer une villa doit le faire
         * tomber immédiatement.
         */
        static::saved(fn () => CatalogCache::flush());
        static::deleted(fn () => CatalogCache::flush());
    }

    // ---------------------------------------------------------------- agrégats

    /**
     * Recalcule la note moyenne et le nombre d'avis depuis les avis publiés.
     *
     * Ces deux colonnes sont un cache : elles évitent une agrégation à chaque
     * affichage de carte. Elles sont réécrites par les événements du modèle
     * Review, jamais à la main — un cache que l'on doit penser à rafraîchir
     * finit toujours par mentir.
     */
    public function recalculateRating(): void
    {
        // toBase() : on veut une ligne d'agrégats, pas un modèle Review — les
        // alias `average` et `total` ne sont pas des colonnes de la table.
        $stats = $this->reviews()->approved()->toBase()
            ->selectRaw('avg(overall) as average, count(*) as total')
            ->first();

        $total = (int) ($stats->total ?? 0);

        // forceFill et non update() : ces deux colonnes sont volontairement
        // absentes de $fillable — ce sont des valeurs calculées, jamais des
        // champs de formulaire. L'assignation de masse les écarterait en silence.
        $this->forceFill([
            'reviews_count' => $total,
            'rating_avg' => $total > 0 ? round((float) $stats->average, 2) : null,
        ])->saveQuietly();
    }

    // ---------------------------------------------------------------- publication

    /**
     * Informations minimales exigées avant publication (§10 du cahier des charges).
     *
     * `destination_id` n'y figure pas : la colonne est NOT NULL, une villa
     * sans destination ne peut pas exister en base.
     *
     * @return list<string> les manques, vide si la villa est publiable
     */
    public function publicationBlockers(): array
    {
        $missing = [];

        if (blank($this->name)) {
            $missing[] = 'name';
        }

        if (blank($this->description?->get())) {
            $missing[] = 'description';
        }

        if ($this->images()->count() === 0) {
            $missing[] = 'images';
        }

        if ($this->base_price->isZero()) {
            $missing[] = 'base_price';
        }

        if (($this->capacity ?? 0) < 1) {
            $missing[] = 'capacity';
        }

        return $missing;
    }

    public function isPublishable(): bool
    {
        return $this->publicationBlockers() === [];
    }

    public function isPublished(): bool
    {
        return $this->status === PropertyStatus::Published;
    }

    // ---------------------------------------------------------------- scopes

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PropertyStatus::Published);
    }

    public function scopeInDestination(Builder $query, Destination|int|string|null $destination): Builder
    {
        if ($destination === null) {
            return $query;
        }

        if ($destination instanceof Destination) {
            return $query->where('destination_id', $destination->id);
        }

        if (is_int($destination) || ctype_digit((string) $destination)) {
            return $query->where('destination_id', (int) $destination);
        }

        return $query->whereHas('destination', fn ($q) => $q->where('slug', $destination));
    }

    public function scopeForGuests(Builder $query, ?int $guests): Builder
    {
        return $guests ? $query->where('capacity', '>=', $guests) : $query;
    }

    public function scopeWithBedroomsAtLeast(Builder $query, ?int $bedrooms): Builder
    {
        return $bedrooms ? $query->where('bedrooms', '>=', $bedrooms) : $query;
    }

    public function scopePriceBetween(Builder $query, ?int $min, ?int $max): Builder
    {
        return $query
            ->when($min !== null, fn ($q) => $q->where('base_price', '>=', $min))
            ->when($max !== null, fn ($q) => $q->where('base_price', '<=', $max));
    }

    /**
     * Ne garde que les villas possédant TOUS les équipements demandés.
     *
     * @param  list<int|string>  $amenities  identifiants ou slugs
     */
    public function scopeWithAllAmenities(Builder $query, array $amenities): Builder
    {
        foreach (array_filter($amenities) as $amenity) {
            $query->whereHas('amenities', fn ($q) => is_int($amenity) || ctype_digit((string) $amenity)
                ? $q->where('amenities.id', (int) $amenity)
                : $q->where('amenities.slug', $amenity)
            );
        }

        return $query;
    }

    /**
     * Villas libres sur toute la période [checkin, checkout).
     *
     * Le test de chevauchement est délégué à PostgreSQL : c'est le même opérateur
     * `&&` que celui de la contrainte d'exclusion, donc exactement la même
     * définition de « libre » à l'affichage et à la réservation.
     */
    public function scopeAvailableBetween(Builder $query, ?string $checkin, ?string $checkout): Builder
    {
        if (blank($checkin) || blank($checkout)) {
            return $query;
        }

        return $query->whereNotExists(
            fn ($sub) => $sub
                ->select(DB::raw(1))
                ->from('availability_blocks')
                ->whereColumn('availability_blocks.property_id', 'properties.id')
                ->whereRaw("availability_blocks.period && daterange(?::date, ?::date, '[)')", [$checkin, $checkout])
        );
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.str_replace('%', '\%', $term).'%';

        return $query->where(fn ($q) => $q
            ->where('name', 'ilike', $like)
            ->orWhere('neighborhood', 'ilike', $like)
            ->orWhereHas('destination', fn ($d) => $d->whereRaw("name->>'fr' ilike ?", [$like]))
        );
    }
}
