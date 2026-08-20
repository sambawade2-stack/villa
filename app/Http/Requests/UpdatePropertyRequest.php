<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Modification d'une villa.
 *
 * Les montants arrivent en francs entiers : le XOF n'a pas de sous-unité, et
 * accepter une décimale ici ouvrirait la porte à des arrondis silencieux.
 */
class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $property = $this->route('property');

        return [
            // Informations
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:170', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('properties', 'slug')->ignore($property?->id)->whereNull('deleted_at')],
            'type' => ['required', Rule::enum(PropertyType::class)],
            'property_owner_id' => ['required', 'integer', 'exists:property_owners,id'],
            'destination_id' => ['required', 'integer', 'exists:destinations,id'],
            'description_fr' => ['nullable', 'string', 'max:8000'],
            'description_en' => ['nullable', 'string', 'max:8000'],
            'short_description_fr' => ['nullable', 'string', 'max:400'],
            'short_description_en' => ['nullable', 'string', 'max:400'],

            // Capacité
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
            'bedrooms' => ['required', 'integer', 'min:1', 'max:20'],
            'beds' => ['required', 'integer', 'min:1', 'max:40'],
            'bathrooms' => ['required', 'integer', 'min:1', 'max:20'],
            'surface_sqm' => ['nullable', 'integer', 'min:10', 'max:5000'],

            // Localisation
            'neighborhood' => ['nullable', 'string', 'max:150'],
            'zone' => ['nullable', 'string', 'max:150'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'internal_address' => ['nullable', 'string', 'max:500'],
            'internal_notes' => ['nullable', 'string', 'max:4000'],

            // Tarifs, en francs entiers
            'base_price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'weekend_price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'weekly_price' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'cleaning_fee' => ['required', 'integer', 'min:0', 'max:10000000'],
            'security_deposit' => ['required', 'integer', 'min:0', 'max:100000000'],

            // Règles
            'min_nights' => ['required', 'integer', 'min:1', 'max:365'],
            'max_nights' => ['nullable', 'integer', 'min:1', 'max:365', 'gte:min_nights'],
            'checkin_time' => ['required', 'date_format:H:i'],
            'checkout_time' => ['required', 'date_format:H:i'],
            'pets_allowed' => ['nullable', 'boolean'],
            'parties_allowed' => ['nullable', 'boolean'],
            'smoking_allowed' => ['nullable', 'boolean'],

            // Équipements
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['integer', 'exists:amenities,id'],

            // Référencement
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'slug.regex' => __('L\'identifiant d\'URL ne peut contenir que des minuscules, des chiffres et des tirets.'),
            'max_nights.gte' => __('La durée maximale doit être supérieure ou égale à la durée minimale.'),
        ];
    }
}
