<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\TranslatableCast;
use App\Concerns\FormatsFileSize;
use App\Support\Translated;
use Database\Factories\PropertyImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $property_id
 * @property string $path
 * @property string $disk
 * @property Translated|null $alt
 * @property int $position
 * @property bool $is_primary
 * @property int|null $width
 * @property int|null $height
 * @property int|null $size
 * @property array<string, mixed>|null $conversions
 * @property Carbon|null $converted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Property|null $property
 */
class PropertyImage extends Model
{
    /** @use HasFactory<PropertyImageFactory> */
    use FormatsFileSize, HasFactory;

    protected $fillable = [
        'property_id', 'path', 'disk', 'alt', 'position', 'is_primary',
        'width', 'height', 'size', 'conversions', 'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'alt' => TranslatableCast::class,
            'conversions' => 'array',
            'is_primary' => 'boolean',
            'converted_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * URL d'un dérivé, avec repli sur l'original tant que le job
     * de conversion n'est pas passé.
     */
    public function url(string $conversion = 'card'): string
    {
        $path = $this->conversions[$conversion] ?? $this->path;

        return Storage::disk($this->disk ?? 'properties')->url($path);
    }

    /** Jeu de sources pour un <img srcset>. */
    public function srcset(string ...$conversions): string
    {
        $widths = ['thumb' => 400, 'card' => 800, 'hero' => 1600, 'full' => 2400];

        return collect($conversions ?: ['thumb', 'card', 'hero'])
            ->filter(fn (string $name) => isset($this->conversions[$name]))
            ->map(fn (string $name) => $this->url($name).' '.$widths[$name].'w')
            ->implode(', ');
    }

    public function hasConversions(): bool
    {
        return $this->converted_at !== null && filled($this->conversions);
    }
}
