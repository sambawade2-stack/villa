<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use App\Support\Money;

/**
 * Résultat immuable d'un calcul de prix.
 *
 * Aucun montant n'est recalculé après coup : ce que porte l'objet est ce qui
 * sera enregistré sur la réservation.
 */
final readonly class Quote
{
    /** @param  list<array{date: string, amount: int, source: string}>  $nights */
    public function __construct(
        public string $checkin,
        public string $checkout,
        public int $nightCount,
        public int $guests,
        public array $nights,
        public Money $nightlySubtotal,
        public Money $cleaningFee,
        public Money $serviceFee,
        public Money $discount,
        public Money $total,
        public Money $securityDeposit,
    ) {}

    /** Prix moyen par nuit, utile à l'affichage quand les nuits diffèrent. */
    public function averageNightly(): Money
    {
        return $this->nightCount > 0
            ? Money::from(intdiv($this->nightlySubtotal->amount, $this->nightCount))
            : Money::zero();
    }

    /** @return array<string, mixed> forme enregistrée dans bookings.price_breakdown */
    public function toArray(): array
    {
        return [
            'checkin' => $this->checkin,
            'checkout' => $this->checkout,
            'nights' => $this->nights,
            'nightly_subtotal' => $this->nightlySubtotal->amount,
            'cleaning_fee' => $this->cleaningFee->amount,
            'service_fee' => $this->serviceFee->amount,
            'discount' => $this->discount->amount,
            'total' => $this->total->amount,
        ];
    }
}
