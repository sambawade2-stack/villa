<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\TranslatableCast;
use App\Support\Translated;
use Database\Factories\AmenityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Translated $name
 * @property string $slug
 * @property string|null $icon
 * @property string $category
 * @property int $position
 * @property bool $is_active
 * @property bool $is_filterable
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Property> $properties
 */
class Amenity extends Model
{
    /** @use HasFactory<AmenityFactory> */
    use HasFactory;

    protected $table = 'amenities';

    protected $fillable = [
        'name', 'slug', 'icon', 'category', 'position', 'is_active', 'is_filterable',
    ];

    protected function casts(): array
    {
        return [
            'name' => TranslatableCast::class,
            'is_active' => 'boolean',
            'is_filterable' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsToMany<Property, $this> */
    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'property_amenity');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Les équipements proposés comme filtres sur la page de recherche. */
    public function scopeFilterable(Builder $query): Builder
    {
        return $query->where('is_filterable', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('category')->orderBy('position')->orderBy('id');
    }
}
