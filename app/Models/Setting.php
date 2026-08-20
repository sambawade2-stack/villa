<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Réglages modifiables sans redéploiement.
 *
 * Lecture toujours passée par le cache Redis : ces valeurs sont consultées
 * à chaque calcul de prix, il serait absurde de les relire en base à chaque fois.
 *
 * @property int $id
 * @property string $key
 * @property array<string, mixed>|null $value
 * @property string $group
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'description'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    private const CACHE_KEY = 'settings.all';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->pluck('value', 'key')->all()
        );

        return $settings[$key] ?? $default;
    }

    public static function put(string $key, mixed $value, string $group = 'general'): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group],
        );
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
