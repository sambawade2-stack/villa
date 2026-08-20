<?php

declare(strict_types=1);

namespace App\Support;

use JsonSerializable;
use Stringable;

/**
 * Valeur traduisible stockée en jsonb : {"fr": "…", "en": "…"}.
 *
 * Affichée dans la locale courante, avec repli sur la locale de secours
 * puis sur la première traduction non vide — un texte manquant en anglais
 * ne doit jamais produire une page vide.
 */
final readonly class Translated implements JsonSerializable, Stringable
{
    /** @param array<string, string|null> $translations */
    public function __construct(private array $translations = []) {}

    /** @param array<string, string|null>|string|null $value */
    public static function make(array|string|null $value, ?string $locale = null): self
    {
        if (is_array($value)) {
            return new self($value);
        }

        if ($value === null || $value === '') {
            return new self;
        }

        return new self([$locale ?? app()->getLocale() => $value]);
    }

    public function get(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        foreach ([$locale, config('app.fallback_locale')] as $candidate) {
            $value = $this->translations[$candidate] ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        foreach ($this->translations as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    public function has(string $locale): bool
    {
        $value = $this->translations[$locale] ?? null;

        return is_string($value) && $value !== '';
    }

    public function with(string $locale, ?string $value): self
    {
        return new self([...$this->translations, $locale => $value]);
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return $this->translations;
    }

    public function __toString(): string
    {
        return $this->get() ?? '';
    }

    /** @return array<string, string|null> */
    public function jsonSerialize(): array
    {
        return $this->translations;
    }
}
