<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $phone
 * @property string|null $whatsapp
 * @property string|null $avatar_path
 * @property UserRole $role
 * @property string $locale
 * @property Carbon|null $last_login_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read string $full_name
 * @property-read string $initials
 * @property-read PropertyOwner|null $propertyOwner
 * @property-read Collection<int, Booking> $bookings
 * @property-read Collection<int, Review> $reviews
 * @property-read Collection<int, Favorite> $favorites
 * @property-read Collection<int, Conversation> $conversations
 * @property-read Collection<int, Property> $favoriteProperties
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /*
     * Le contrat MustVerifyEmail suffit : Illuminate\Foundation\Auth\User
     * (dont cette classe hérite) utilise déjà le trait Illuminate\Auth\
     * MustVerifyEmail, qui fournit hasVerifiedEmail(), markEmailAsVerified()
     * et sendEmailVerificationNotification(). L'implémenter ici ne fait
     * qu'activer le contrat — voir AppServiceProvider pour l'écouteur qui
     * envoie réellement le courriel à l'inscription.
     */

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'password',
        'phone', 'whatsapp', 'avatar_path', 'locale',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    // ---------------------------------------------------------------- relations

    /** Le dossier propriétaire rattaché à ce compte — nul en v1. */
    public function propertyOwner(): HasOne
    {
        return $this->hasOne(PropertyOwner::class);
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** @return HasMany<Favorite, $this> */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /** @return HasMany<Conversation, $this> */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Les villas mises en favori, directement.
     *
     * @return BelongsToMany<Property, $this>
     */
    public function favoriteProperties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'favorites')->withTimestamps();
    }

    // ---------------------------------------------------------------- attributs

    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim("{$this->first_name} {$this->last_name}"));
    }

    protected function initials(): Attribute
    {
        return Attribute::get(fn (): string => mb_strtoupper(
            mb_substr($this->first_name ?? '', 0, 1).mb_substr($this->last_name ?? '', 0, 1)
        ));
    }

    // ---------------------------------------------------------------- rôles

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    public function hasFavorited(Property $property): bool
    {
        return $this->favorites()->where('property_id', $property->id)->exists();
    }

    // ---------------------------------------------------------------- scopes

    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', UserRole::Admin);
    }

    public function scopeCustomers(Builder $query): Builder
    {
        return $query->where('role', UserRole::Customer);
    }
}
