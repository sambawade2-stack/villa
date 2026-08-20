<?php

declare(strict_types=1);

use App\Support\Money;

/*
|--------------------------------------------------------------------------
| Montants en francs CFA
|--------------------------------------------------------------------------
|
| Le XOF n'a pas de sous-unité : un montant est toujours un entier de francs.
| Ces tests fixent le comportement d'arrondi, qui doit rester explicite.
|
*/

it('additionne et soustrait sans dérive', function () {
    $a = Money::from(250_000);
    $b = Money::from(75_000);

    expect($a->plus($b)->amount)->toBe(325_000)
        ->and($a->minus($b)->amount)->toBe(175_000)
        ->and(Money::sum($a, $b, Money::from(1_000))->amount)->toBe(326_000);
});

it('ne descend jamais sous zéro à la soustraction', function () {
    // Cas réel : une remise supérieure au sous-total.
    expect(Money::from(10_000)->minus(Money::from(25_000))->amount)->toBe(0);
});

it('refuse un montant négatif', function () {
    expect(fn () => Money::from(-1))->toThrow(InvalidArgumentException::class);
});

it('multiplie par un nombre de nuits', function () {
    expect(Money::from(250_000)->times(7)->amount)->toBe(1_750_000);
});

it('calcule une commission exacte', function () {
    // L'exemple du cahier des charges : 1 000 000 à 10 % = 100 000.
    $base = Money::from(1_000_000);
    $commission = $base->percentage('10');

    expect($commission->amount)->toBe(100_000)
        ->and($base->minus($commission)->amount)->toBe(900_000);
});

it('arrondit au franc, au demi supérieur', function (int $amount, string $rate, int $expected) {
    expect(Money::from($amount)->percentage($rate)->amount)->toBe($expected);
})->with([
    'pas de reste' => [1_000_000, '10', 100_000],
    'reste vers le bas' => [333_333, '10', 33_333],
    'reste vers le haut' => [333_335, '10', 33_334],
    'demi arrondi au-dessus' => [10_005, '10', 1_001],
    'taux décimal' => [1_234_567, '2.5', 30_864],
    'taux nul' => [500_000, '0', 0],
    'taux plein' => [500_000, '100', 500_000],
]);

it('conserve la somme après partage commission / propriétaire', function (int $total) {
    $base = Money::from($total);
    $commission = $base->percentage('10');
    $payout = $base->minus($commission);

    // La base impose commission + payout = base : le calcul doit le garantir.
    expect($commission->plus($payout)->amount)->toBe($total);
})->with([1_000_000, 333_333, 1, 7, 999_999, 12_345_678]);

it('formate à la française', function () {
    expect(Money::from(250_000)->format())->toBe("250\u{202F}000\u{202F}FCFA")
        ->and(Money::from(1_750_000)->format(withCurrency: false))->toBe("1\u{202F}750\u{202F}000")
        ->and(Money::from(0)->format())->toBe("0\u{202F}FCFA");
});

it('se sérialise en entier dans du JSON', function () {
    expect(json_encode(['total' => Money::from(250_000)]))->toBe('{"total":250000}');
});
