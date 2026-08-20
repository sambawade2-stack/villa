<?php

declare(strict_types=1);

namespace App\Casts;

use App\Support\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * bigint (francs) <-> Money.
 *
 * @implements CastsAttributes<Money|null, mixed>
 */
final class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        return $value === null ? null : Money::from((int) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return match (true) {
            $value === null => null,
            $value instanceof Money => $value->amount,
            is_int($value) => Money::from($value)->amount,
            is_string($value) && ctype_digit($value) => Money::from((int) $value)->amount,
            default => throw new InvalidArgumentException(
                sprintf('%s::$%s attend un Money ou un entier de francs, %s reçu.', $model::class, $key, get_debug_type($value))
            ),
        };
    }
}
