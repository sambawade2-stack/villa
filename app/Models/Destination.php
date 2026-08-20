<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\TranslatableCast;
use App\Support\CatalogCache;
use App\Support\Translated;
use Database\Factories\DestinationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Translated $name
 * @property string $slug
 * @property Translated|null $description
 * @property string $region
 * @property string|null $hero_image_path
 * @property string|null $latitude
 * @property string|null $longitude
 * @property int $position
 * @property bool $is_active
 * @property bool $is_featured
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Property> $properties
 * @property-read Collection<int, Model> $publishedProperties
 */
class Destination extends Model
{
    /** @use HasFactory<DestinationFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'region', 'hero_image_path',
        'latitude', 'longitude', 'position', 'is_active', 'is_featured',
        'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'name' => TranslatableCast::class,
            'description' => TranslatableCast::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        // Le cache du catalogue tombe dès qu'une destination bouge.
        static::saved(fn () => CatalogCache::flush());
        static::deleted(fn () => CatalogCache::flush());
    }

    /** @return HasMany<Property, $this> */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    /** Les villas réellement visibles du public. */
    public function publishedProperties(): HasMany
    {
        return $this->properties()->published();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }
}
