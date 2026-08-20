<?php

declare(strict_types=1);

namespace App\Casts;

use App\Support\Translated;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * jsonb {"fr": …, "en": …} <-> Translated.
 *
 * @implements CastsAttributes<Translated, Translated|array<string, string|null>|string|null>
 */
final class TranslatableCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): Translated
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;

        return new Translated(is_array($decoded) ? $decoded : []);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        $translations = match (true) {
            $value instanceof Translated => $value->toArray(),
            is_array($value) => $value,
            default => Translated::make($value)->toArray(),
        };

        // On ne conserve pas de locale vide : elles fausseraient les replis.
        $translations = array_filter(
            $translations,
            fn ($text) => is_string($text) && trim($text) !== ''
        );

        return json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
