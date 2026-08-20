<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Critères de la page de recherche.
 *
 * Une recherche est publique : la validation ne sert pas à refuser l'accès mais
 * à empêcher qu'une chaîne arbitraire de l'URL atteigne la requête SQL.
 */
class SearchVillasRequest extends FormRequest
{
    public const SORTS = ['pertinence', 'prix-croissant', 'prix-decroissant', 'note', 'nouveautes'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'destination' => ['nullable', 'string', 'exists:destinations,slug'],
            'checkin' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'checkout' => ['nullable', 'date_format:Y-m-d', 'after:checkin', 'required_with:checkin'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:50'],
            'bedrooms' => ['nullable', 'integer', 'min:1', 'max:20'],
            'bathrooms' => ['nullable', 'integer', 'min:1', 'max:20'],
            'price_min' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'price_max' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'amenities' => ['nullable', 'array', 'max:30'],
            'amenities.*' => ['string', 'exists:amenities,slug'],
            'verified' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(self::SORTS)],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Une date d'arrivée sans départ ne filtre rien : on ignore les deux
        // plutôt que de renvoyer une erreur sur une simple URL tronquée.
        if ($this->filled('checkin') && ! $this->filled('checkout')) {
            $this->merge(['checkin' => null, 'checkout' => null]);
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'checkout.after' => __('La date de départ doit suivre la date d\'arrivée.'),
            'checkin.after_or_equal' => __('La date d\'arrivée ne peut pas être dans le passé.'),
        ];
    }
}
