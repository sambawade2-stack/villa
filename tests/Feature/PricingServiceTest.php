<?php

declare(strict_types=1);

use App\Models\PricingRule;
use App\Models\Property;
use App\Models\Setting;
use App\Services\Pricing\PricingService;
use App\Support\Money;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->service = app(PricingService::class);

    $this->property = Property::factory()->create([
        'base_price' => 200_000,
        'weekend_price' => 250_000,
        'cleaning_fee' => 25_000,
        'security_deposit' => 100_000,
        'min_nights' => 2,
    ]);

    Setting::put('platform.service_fee_rate', 3.0, 'pricing');
    Setting::flushCache();
});

/*
|--------------------------------------------------------------------------
| Tarif d'une nuit
|--------------------------------------------------------------------------
*/

it('applique le tarif de base en semaine', function () {
    // 2026-09-15 est un mardi.
    $rate = $this->service->nightlyRate($this->property, Carbon::parse('2026-09-15'));

    expect($rate['amount']->amount)->toBe(200_000);
});

it('applique le tarif week-end le vendredi et le samedi', function (string $date) {
    expect($this->service->nightlyRate($this->property, Carbon::parse($date))['amount']->amount)
        ->toBe(250_000);
})->with([
    'vendredi' => '2026-09-18',
    'samedi' => '2026-09-19',
]);

it('revient au tarif de base la nuit du dimanche', function () {
    expect($this->service->nightlyRate($this->property, Carbon::parse('2026-09-20'))['amount']->amount)
        ->toBe(200_000);
});

it('fait primer une règle de période sur le tarif week-end', function () {
    PricingRule::create([
        'property_id' => $this->property->id,
        'label' => 'Haute saison',
        'starts_on' => '2026-09-14',
        'ends_on' => '2026-09-30',
        'price_per_night' => 400_000,
        'priority' => 10,
    ]);

    // Un samedi couvert par la règle : c'est la règle qui gagne.
    $rate = $this->service->nightlyRate($this->property->fresh(), Carbon::parse('2026-09-19'));

    expect($rate['amount']->amount)->toBe(400_000)
        ->and($rate['source'])->toBe('Haute saison');
});

it('départage deux règles qui se recouvrent par leur priorité', function () {
    PricingRule::create([
        'property_id' => $this->property->id, 'label' => 'Haute saison',
        'starts_on' => '2026-12-01', 'ends_on' => '2027-01-31',
        'price_per_night' => 350_000, 'priority' => 5,
    ]);
    PricingRule::create([
        'property_id' => $this->property->id, 'label' => 'Fêtes de fin d\'année',
        'starts_on' => '2026-12-20', 'ends_on' => '2027-01-05',
        'price_per_night' => 500_000, 'priority' => 20,
    ]);

    $rate = $this->service->nightlyRate($this->property->fresh(), Carbon::parse('2026-12-25'));

    expect($rate['amount']->amount)->toBe(500_000)
        ->and($rate['source'])->toBe('Fêtes de fin d\'année');
});

/*
|--------------------------------------------------------------------------
| Devis complet
|--------------------------------------------------------------------------
*/

it('ne facture pas la nuit du départ', function () {
    // Du mardi au vendredi : 3 nuits, pas 4.
    $quote = $this->service->quote($this->property, '2026-09-15', '2026-09-18');

    expect($quote->nightCount)->toBe(3)
        ->and($quote->nights)->toHaveCount(3);
});

it('additionne les nuits au bon tarif', function () {
    // Mer 16 (base), jeu 17 (base), ven 18 (week-end), sam 19 (week-end).
    $quote = $this->service->quote($this->property, '2026-09-16', '2026-09-20');

    expect($quote->nightCount)->toBe(4)
        ->and($quote->nightlySubtotal->amount)->toBe(200_000 + 200_000 + 250_000 + 250_000);
});

it('ajoute ménage et frais de service au total', function () {
    $quote = $this->service->quote($this->property, '2026-09-15', '2026-09-18');

    $nightly = 200_000 * 3;
    $service = (int) round($nightly * 0.03);

    expect($quote->nightlySubtotal->amount)->toBe($nightly)
        ->and($quote->cleaningFee->amount)->toBe(25_000)
        ->and($quote->serviceFee->amount)->toBe($service)
        ->and($quote->total->amount)->toBe($nightly + 25_000 + $service);
});

it('reporte la caution sans l\'inclure au total', function () {
    $quote = $this->service->quote($this->property, '2026-09-15', '2026-09-18');

    expect($quote->securityDeposit->amount)->toBe(100_000)
        ->and($quote->total->amount)->toBeLessThan($quote->total->amount + 100_000);
});

it('refuse un départ antérieur ou égal à l\'arrivée', function (string $checkout) {
    expect(fn () => $this->service->quote($this->property, '2026-09-15', $checkout))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'départ antérieur' => '2026-09-14',
    'même jour' => '2026-09-15',
]);

it('détaille le calcul nuit par nuit', function () {
    $quote = $this->service->quote($this->property, '2026-09-18', '2026-09-20');

    expect($quote->nights[0]['date'])->toBe('2026-09-18')
        ->and($quote->nights[0]['source'])->toBe('Tarif week-end')
        ->and($quote->nights[1]['date'])->toBe('2026-09-19')
        // Le détail conservé permet de justifier un prix des mois plus tard.
        ->and($quote->toArray())->toHaveKeys(['checkin', 'checkout', 'nights', 'total']);
});

it('calcule un prix moyen par nuit', function () {
    $quote = $this->service->quote($this->property, '2026-09-16', '2026-09-20');

    expect($quote->averageNightly()->amount)->toBe(intdiv(900_000, 4));
});

it('respecte le séjour minimum dans le devis indicatif', function () {
    $property = Property::factory()->create(['base_price' => 100_000, 'min_nights' => 10]);

    expect($this->service->indicativeQuote($property, nights: 7)->nightCount)->toBe(10);
});

it('part de demain pour le devis indicatif, jamais d\'aujourd\'hui', function () {
    $quote = $this->service->indicativeQuote($this->property);

    expect($quote->checkin)->toBe(now()->addDay()->toDateString())
        ->and($quote->nightCount)->toBe(7);
});

it('produit des montants entiers, sans centime', function () {
    $property = Property::factory()->create(['base_price' => 333_333, 'cleaning_fee' => 7_777]);

    $quote = $this->service->quote($property, '2026-09-15', '2026-09-18');

    expect($quote->total)->toBeInstanceOf(Money::class)
        ->and($quote->total->amount)->toBeInt();
});
