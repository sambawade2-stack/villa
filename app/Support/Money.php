<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Montant en francs CFA.
 *
 * Le XOF n'a pas de sous-unité : il n'existe pas de centime de franc.
 * Un montant est donc toujours un entier de francs, jamais un flottant.
 * Les pourcentages passent par bcmath pour éviter toute dérive binaire.
 */
final readonly class Money implements JsonSerializable, Stringable
{
    public const CURRENCY = 'XOF';

    private function __construct(public int $amount)
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Un montant négatif n'a pas de sens ici : {$amount}.");
        }
    }

    public static function from(int $francs): self
    {
        return new self($francs);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function sum(self ...$amounts): self
    {
        return new self(array_sum(array_map(fn (self $m) => $m->amount, $amounts)));
    }

    public function plus(self $other): self
    {
        return new self($this->amount + $other->amount);
    }

    /** Soustraction plancher : ne descend jamais sous zéro (remise > total, par exemple). */
    public function minus(self $other): self
    {
        return new self(max(0, $this->amount - $other->amount));
    }

    public function times(int $factor): self
    {
        if ($factor < 0) {
            throw new InvalidArgumentException("Facteur négatif : {$factor}.");
        }

        return new self($this->amount * $factor);
    }

    /**
     * Applique un taux exprimé en pourcentage (« 10 » = 10 %).
     *
     * bcmath fait le calcul en décimal exact, puis on arrondit au franc
     * au demi supérieur — un arrondi explicite, jamais implicite.
     */
    public function percentage(string|float|int $rate): self
    {
        $rate = (string) $rate;

        if (bccomp($rate, '0', 6) < 0) {
            throw new InvalidArgumentException("Taux négatif : {$rate}.");
        }

        $exact = bcdiv(bcmul((string) $this->amount, $rate, 6), '100', 6);

        return new self((int) round((float) $exact));
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount;
    }

    public function greaterThan(self $other): bool
    {
        return $this->amount > $other->amount;
    }

    public function lessThan(self $other): bool
    {
        return $this->amount < $other->amount;
    }

    /** « 250 000 FCFA » — espace fine insécable, comme le veut l'usage typographique français. */
    public function format(bool $withCurrency = true): string
    {
        $formatted = number_format($this->amount, 0, ',', "\u{202F}");

        return $withCurrency ? $formatted."\u{202F}FCFA" : $formatted;
    }

    public function __toString(): string
    {
        return $this->format();
    }

    public function jsonSerialize(): int
    {
        return $this->amount;
    }
}
