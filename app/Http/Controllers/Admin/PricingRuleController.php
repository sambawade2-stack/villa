<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingRule;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Tarifs de saison d'une villa.
 *
 * Le chevauchement entre règles est volontairement permis — « haute saison »
 * et « fêtes de fin d'année » se recouvrent légitimement — et c'est la
 * priorité qui les départage à l'affichage, dans
 * PricingService::nightlyRate(). Rien n'empêche donc de créer deux règles sur
 * les mêmes dates ; c'est le comportement voulu, pas un oubli de validation.
 */
class PricingRuleController extends Controller
{
    public function store(Request $request, Property $property): RedirectResponse
    {
        $property->pricingRules()->create($this->validated($request));

        return back()->with('status', __('Tarif de saison ajouté.'));
    }

    public function update(Request $request, Property $property, PricingRule $rule): RedirectResponse
    {
        $this->assertBelongsTo($property, $rule);

        $rule->update($this->validated($request));

        return back()->with('status', __('Tarif de saison mis à jour.'));
    }

    public function destroy(Property $property, PricingRule $rule): RedirectResponse
    {
        $this->assertBelongsTo($property, $rule);

        $rule->delete();

        return back()->with('status', __('Tarif de saison supprimé.'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            // En francs CFA entiers : le XOF n'a pas de sous-unité.
            'price_per_night' => ['required', 'integer', 'min:0', 'max:100000000'],
            'min_nights' => ['nullable', 'integer', 'min:1', 'max:365'],
            'priority' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
    }

    /** Empêche d'agir sur le tarif d'une villa via l'URL d'une autre. */
    private function assertBelongsTo(Property $property, PricingRule $rule): void
    {
        abort_unless($rule->property_id === $property->id, 404);
    }
}
