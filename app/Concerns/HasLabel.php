<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Str;

/**
 * Donne à un enum un libellé traduit, résolu depuis lang/{locale}/enums.php.
 *
 * BookingStatus::Confirmed->label() lit enums.booking_status.confirmed
 */
trait HasLabel
{
    public function label(): string
    {
        return __($this->translationKey());
    }

    public function translationKey(): string
    {
        return 'enums.'.Str::snake(class_basename($this)).'.'.$this->value;
    }

    /** @return array<string, string> valeur => libellé, pour les <select> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
