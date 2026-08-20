<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OwnerStatus;
use Database\Factories\PropertyOwnerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Propriétaire d'une ou plusieurs villas.
 *
 * En v1 il s'agit d'un contact interne, sans compte : $user_id reste nul.
 * C'est cette colonne qui ouvrira le portail propriétaire en v2, sans
 * rien changer aux villas, réservations ou paiements déjà en base.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $first_name
 * @property string $last_name
 * @property string $phone
 * @property string|null $whatsapp
 * @property string|null $email
 * @property string|null $city
 * @property string|null $internal_address
 * @property string|null $internal_notes
 * @property OwnerStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read string $full_name
 * @property-read User|null $user
 * @property-read Collection<int, Property> $properties
 * @property-read Collection<int, Booking> $bookings
 * @property-read Collection<int, Commission> $commissions
 */
class PropertyOwner extends Model
{
    /** @use HasFactory<PropertyOwnerFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'first_name', 'last_name', 'phone', 'whatsapp',
        'email', 'city', 'internal_address', 'internal_notes', 'status',
    ];

    protected function casts(): array
    {
        return ['status' => OwnerStatus::class];
    }

    // ---------------------------------------------------------------- relations

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Property, $this> */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    /** @return HasManyThrough<Booking, Property, $this> */
    public function bookings(): HasManyThrough
    {
        return $this->hasManyThrough(Booking::class, Property::class);
    }

    /** @return HasMany<Commission, $this> */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    // ---------------------------------------------------------------- attributs

    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim("{$this->first_name} {$this->last_name}"));
    }

    /** Vrai le jour où ce propriétaire disposera d'un accès — v2. */
    public function hasAccount(): bool
    {
        return $this->user_id !== null;
    }

    // ---------------------------------------------------------------- scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', OwnerStatus::Active);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.str_replace('%', '\%', $term).'%';

        return $query->where(fn ($q) => $q
            ->where('first_name', 'ilike', $like)
            ->orWhere('last_name', 'ilike', $like)
            ->orWhere('phone', 'ilike', $like)
            ->orWhere('email', 'ilike', $like)
        );
    }
}
