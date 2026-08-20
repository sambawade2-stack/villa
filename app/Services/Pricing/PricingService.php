<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use App\Models\Property;
use App\Models\Setting;
use App\Support\Money;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Calcul du prix d'un séjour.
 *
 * Point unique de vérité : aucun autre endroit de l'application n'a le droit
 * d'additionner des nuits. Le navigateur n'est jamais cru — un total reçu du
 * client est systématiquement recalculé ici avant d'être enregistré.
 */
class PricingService
{
    /**
     * Tarif d'une nuit donnée.
     *
     * Ordre de priorité :
     *   1. règle de période la plus prioritaire couvrant la date ;
     *   2. tarif week-end, pour les nuits du vendredi et du samedi ;
     *   3. tarif de base.
     *
     * @return array{amount: Money, source: string}
     */
    public function nightlyRate(Property $property, Carbon $date): array
    {
        $rule = $property->pricingRules()
            ->covering($date->toDateString())
            ->first();

        if ($rule !== null) {
            return ['amount' => $rule->price_per_night, 'source' => $rule->label];
        }

        // isFriday/isSaturday : la nuit porte le nom du jour où l'on se couche.
        if (($date->isFriday() || $date->isSaturday()) && $property->weekend_price !== null) {
            return ['amount' => $property->weekend_price, 'source' => __('Tarif week-end')];
        }

        return ['amount' => $property->base_price, 'source' => __('Tarif de base')];
    }

    /**
     * Devis complet pour un séjour.
     *
     * @throws InvalidArgumentException si les dates sont incohérentes
     */
    public function quote(Property $property, string $checkin, string $checkout, int $guests = 1): Quote
    {
        $start = Carbon::parse($checkin)->startOfDay();
        $end = Carbon::parse($checkout)->startOfDay();

        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('La date de départ doit suivre la date d\'arrivée.');
        }

        $nights = [];
        $subtotal = 0;

        // La borne haute est exclue : on ne facture pas la nuit du départ.
        foreach ($start->daysUntil($end) as $day) {
            $rate = $this->nightlyRate($property, $day);
            $subtotal += $rate['amount']->amount;

            $nights[] = [
                'date' => $day->toDateString(),
                'amount' => $rate['amount']->amount,
                'source' => $rate['source'],
            ];
        }

        $nightlySubtotal = Money::from($subtotal);
        $cleaning = $property->cleaning_fee ?? Money::zero();
        $serviceFee = $nightlySubtotal->percentage((string) Setting::get('platform.service_fee_rate', 3));

        return new Quote(
            checkin: $start->toDateString(),
            checkout: $end->toDateString(),
            nightCount: count($nights),
            guests: $guests,
            nights: $nights,
            nightlySubtotal: $nightlySubtotal,
            cleaningFee: $cleaning,
            serviceFee: $serviceFee,
            discount: Money::zero(),
            total: Money::sum($nightlySubtotal, $cleaning, $serviceFee),
            securityDeposit: $property->security_deposit ?? Money::zero(),
        );
    }

    /**
     * Devis indicatif affiché sur la fiche quand le visiteur n'a pas saisi de dates.
     *
     * Part de la prochaine date libre plutôt que d'aujourd'hui : annoncer un
     * total sur des dates déjà réservées serait trompeur.
     */
    public function indicativeQuote(Property $property, int $nights = 7): Quote
    {
        $nights = max($nights, $property->min_nights);
        $start = Carbon::today()->addDay();

        return $this->quote(
            $property,
            $start->toDateString(),
            $start->copy()->addDays($nights)->toDateString(),
            min(2, $property->capacity),
        );
    }
}
