<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\FormatsFileSize;
use App\Enums\ComplianceItem;
use App\Enums\ComplianceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Une pièce du dossier de conformité d'une villa.
 *
 * Le modèle n'expose jamais le chemin du document : `$hidden` le retire de
 * toute sérialisation, et aucune URL publique n'est calculable. Le fichier ne
 * sort que par la route d'administration, qui le diffuse en flux après
 * contrôle des droits.
 *
 * @property int $id
 * @property int $property_id
 * @property ComplianceItem $item
 * @property ComplianceStatus $status
 * @property string|null $document_path
 * @property string|null $document_name
 * @property string|null $document_mime
 * @property int|null $document_size
 * @property string|null $reference
 * @property Carbon|null $issued_on
 * @property Carbon|null $expires_on
 * @property string|null $notes
 * @property int|null $verified_by
 * @property Carbon|null $verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Property|null $property
 * @property-read User|null $verifier
 */
class ComplianceCheck extends Model
{
    use FormatsFileSize;

    protected $fillable = [
        'property_id', 'item', 'status',
        'document_path', 'document_name', 'document_mime', 'document_size',
        'reference', 'issued_on', 'expires_on', 'notes',
        'verified_by', 'verified_at',
    ];

    /** Ni le chemin ni les notes internes ne quittent l'administration. */
    protected $hidden = ['document_path', 'notes'];

    /** Le disque privé, hors de toute racine servie par le serveur web. */
    public const DISK = 'compliance';

    /** La colonne de taille porte un autre nom sur ce modèle. */
    protected function fileSizeColumn(): string
    {
        return 'document_size';
    }

    protected function casts(): array
    {
        return [
            'item' => ComplianceItem::class,
            'status' => ComplianceStatus::class,
            'issued_on' => 'date',
            'expires_on' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function hasDocument(): bool
    {
        return $this->document_path !== null;
    }

    /** La date de validité est-elle dépassée ? */
    public function hasExpired(): bool
    {
        return $this->expires_on !== null && $this->expires_on->isPast();
    }

    /** Expire dans moins de deux mois : à renouveler sans attendre. */
    public function expiresSoon(): bool
    {
        return $this->expires_on !== null
            && ! $this->hasExpired()
            && $this->expires_on->lessThanOrEqualTo(now()->addMonths(2));
    }

    /** Supprime le fichier du disque privé en même temps que la ligne. */
    protected static function booted(): void
    {
        static::deleting(function (self $check) {
            if ($check->document_path !== null) {
                Storage::disk(self::DISK)->delete($check->document_path);
            }
        });
    }

    public function scopeSatisfied(Builder $query): Builder
    {
        return $query->whereIn('status', [ComplianceStatus::Verified, ComplianceStatus::NotApplicable]);
    }

    public function scopeNeedingAttention(Builder $query): Builder
    {
        return $query->whereIn('status', [ComplianceStatus::Rejected, ComplianceStatus::Expired]);
    }
}
