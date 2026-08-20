<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Création d'une villa.
 *
 * Volontairement minimale : on crée un brouillon avec le strict nécessaire,
 * puis on l'enrichit section par section. Demander trente champs d'un coup
 * ferait perdre la saisie au premier oubli.
 */
class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'property_owner_id' => ['required', 'integer', 'exists:property_owners,id'],
            'destination_id' => ['required', 'integer', 'exists:destinations,id'],
            'type' => ['required', Rule::enum(PropertyType::class)],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
            'bedrooms' => ['required', 'integer', 'min:1', 'max:20'],
            'bathrooms' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }
}
