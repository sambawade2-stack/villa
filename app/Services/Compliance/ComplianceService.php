<?php

declare(strict_types=1);

namespace App\Services\Compliance;

use App\Enums\ComplianceItem;
use App\Enums\ComplianceStatus;
use App\Models\ComplianceCheck;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Dossier de conformité d'une villa.
 *
 * Seul endroit de l'application autorisé à écrire sur le disque privé
 * « compliance » et à décider si une villa est vérifiée.
 */
class ComplianceService
{
    /** Types de fichiers acceptés en pièce jointe. */
    public const ALLOWED_MIMES = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

    public const MAX_SIZE_KB = 8192;

    /** Crée les lignes manquantes du dossier. Idempotent. */
    public function ensureChecklist(Property $property): void
    {
        $existing = $property->complianceChecks()->pluck('item')->all();

        $missing = collect(ComplianceItem::ordered())
            ->reject(fn (ComplianceItem $item) => in_array($item->value, array_map(
                fn ($value) => $value instanceof ComplianceItem ? $value->value : $value,
                $existing
            ), true))
            ->map(fn (ComplianceItem $item) => [
                'property_id' => $property->id,
                'item' => $item->value,
                'status' => ComplianceStatus::Pending->value,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        if ($missing !== []) {
            ComplianceCheck::insert($missing);
        }
    }

    /**
     * Dossier complet, dans l'ordre défini, avec les statuts à jour.
     *
     * @return Collection<int, ComplianceCheck>
     */
    public function checklist(Property $property): Collection
    {
        $this->ensureChecklist($property);

        $checks = $property->complianceChecks()->with('verifier')->get()
            ->keyBy(fn (ComplianceCheck $check) => $check->item->value);

        return collect(ComplianceItem::ordered())
            ->map(fn (ComplianceItem $item) => $checks[$item->value])
            ->values();
    }

    /**
     * Statut global du dossier.
     *
     * Vérifié seulement si toutes les pièces requises le sont. Un élément
     * conditionnel écarté (« sans objet ») ne bloque pas ; une pièce refusée
     * ou périmée, si.
     *
     * @return array{status: string, satisfied: int, total: int, blocking: list<string>}
     */
    public function summary(Property $property): array
    {
        $checks = $this->checklist($property);

        $satisfied = $checks->filter(fn (ComplianceCheck $c) => $c->status->satisfies())->count();
        $blocking = $checks
            ->filter(fn (ComplianceCheck $c) => ! $c->status->satisfies())
            ->map(fn (ComplianceCheck $c) => $c->item->value)
            ->values()
            ->all();

        $attention = $checks->contains(fn (ComplianceCheck $c) => $c->status->needsAttention() || $c->hasExpired());

        return [
            'status' => match (true) {
                $blocking === [] => 'verified',
                $attention => 'blocked',
                default => 'incomplete',
            },
            'satisfied' => $satisfied,
            'total' => $checks->count(),
            'blocking' => $blocking,
        ];
    }

    /**
     * Dépose une pièce sur le disque privé.
     *
     * Le nom d'origine n'est jamais réutilisé comme nom de fichier : on garde
     * un identifiant aléatoire, et l'extension est déduite du contenu réel.
     */
    public function attachDocument(ComplianceCheck $check, UploadedFile $file, User $admin): ComplianceCheck
    {
        return DB::transaction(function () use ($check, $file, $admin) {
            $previous = $check->document_path;

            $path = $file->storeAs(
                (string) $check->property_id,
                Str::uuid()->toString().'.'.$file->extension(),
                ['disk' => ComplianceCheck::DISK]
            );

            $check->update([
                'document_path' => $path,
                'document_name' => $file->getClientOriginalName(),
                'document_mime' => $file->getMimeType(),
                'document_size' => $file->getSize(),
                // Déposer n'est pas vérifier : la pièce attend un contrôle humain.
                'status' => ComplianceStatus::Provided,
                'verified_by' => null,
                'verified_at' => null,
            ]);

            if ($previous !== null) {
                Storage::disk(ComplianceCheck::DISK)->delete($previous);
            }

            $this->syncPropertyVerification($check->property, $admin);

            return $check->fresh();
        });
    }

    public function markStatus(
        ComplianceCheck $check,
        ComplianceStatus $status,
        User $admin,
        ?string $notes = null,
    ): ComplianceCheck {
        return DB::transaction(function () use ($check, $status, $admin, $notes) {
            $check->update([
                'status' => $status,
                'notes' => $notes ?? $check->notes,
                'verified_by' => $status === ComplianceStatus::Verified ? $admin->id : null,
                'verified_at' => $status === ComplianceStatus::Verified ? now() : null,
            ]);

            $this->syncPropertyVerification($check->property, $admin);

            return $check->fresh();
        });
    }

    /** Repasse en « périmé » toute pièce dont la validité est dépassée. */
    public function refreshExpirations(Property $property): int
    {
        return $property->complianceChecks()
            ->where('status', ComplianceStatus::Verified)
            ->whereNotNull('expires_on')
            ->whereDate('expires_on', '<', now()->toDateString())
            ->update(['status' => ComplianceStatus::Expired]);
    }

    /**
     * Le badge « Villa vérifiée » découle du dossier, il ne se coche pas à la main.
     *
     * C'est le point qui donne son sens au badge public : il ne peut pas être
     * vrai sans dossier complet.
     */
    public function syncPropertyVerification(Property $property, ?User $admin = null): bool
    {
        $this->refreshExpirations($property);

        $verified = $this->summary($property)['status'] === 'verified';

        if ($property->is_verified !== $verified) {
            $property->forceFill(['is_verified' => $verified])->saveQuietly();
        }

        return $verified;
    }
}
