<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ComplianceStatus;
use App\Http\Controllers\Controller;
use App\Models\ComplianceCheck;
use App\Models\Property;
use App\Services\Compliance\ComplianceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Dossier de conformité d'une villa.
 *
 * Tout ce contrôleur est derrière les middlewares `auth` et `admin`. Aucune de
 * ses données ne transite par une vue publique.
 */
class ComplianceController extends Controller
{
    public function __construct(private readonly ComplianceService $compliance) {}

    public function show(Property $property): View
    {
        return view('admin.villas.compliance', [
            'property' => $property->load(['destination', 'owner']),
            'checks' => $this->compliance->checklist($property),
            'summary' => $this->compliance->summary($property),
        ]);
    }

    public function upload(Request $request, Property $property, ComplianceCheck $check): RedirectResponse
    {
        $this->assertBelongsTo($property, $check);

        abort_unless($check->item->requiresDocument(), 422);

        $request->validate([
            'document' => [
                'required', 'file',
                'mimes:'.implode(',', ComplianceService::ALLOWED_MIMES),
                'max:'.ComplianceService::MAX_SIZE_KB,
            ],
        ]);

        $this->compliance->attachDocument($check, $request->file('document'), $request->user());

        return back()->with('status', __('Pièce déposée. Elle attend votre contrôle.'));
    }

    public function updateStatus(Request $request, Property $property, ComplianceCheck $check): RedirectResponse
    {
        $this->assertBelongsTo($property, $check);

        $data = $request->validate([
            'status' => ['required', Rule::enum(ComplianceStatus::class)],
            'reference' => ['nullable', 'string', 'max:120'],
            'issued_on' => ['nullable', 'date'],
            'expires_on' => ['nullable', 'date', 'after:issued_on'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $status = ComplianceStatus::from($data['status']);

        // « Sans objet » n'a de sens que pour une pièce conditionnelle : on ne
        // laisse pas écarter une pièce obligatoire d'un clic.
        if ($status === ComplianceStatus::NotApplicable && ! $check->item->isConditional()) {
            return back()->with('error', __('Cette pièce est obligatoire : elle ne peut pas être écartée.'));
        }

        // On ne peut pas déclarer vérifiée une pièce dont le document manque.
        if ($status === ComplianceStatus::Verified && $check->item->requiresDocument() && ! $check->hasDocument()) {
            return back()->with('error', __('Déposez la pièce avant de la déclarer vérifiée.'));
        }

        $check->fill([
            'reference' => $data['reference'] ?? null,
            'issued_on' => $data['issued_on'] ?? null,
            'expires_on' => $data['expires_on'] ?? null,
        ])->save();

        $this->compliance->markStatus($check, $status, $request->user(), $data['notes'] ?? null);

        return back()->with('status', __('Dossier mis à jour.'));
    }

    /**
     * Identité du propriétaire : des champs simples (nom, CNI), jamais un
     * document — contrairement aux autres pièces. Le numéro de CNI vit sur
     * le propriétaire, pas sur ce contrôle : un propriétaire avec plusieurs
     * villas n'a qu'une seule identité à vérifier, pas une par villa.
     */
    public function updateIdentity(Request $request, Property $property, ComplianceCheck $check): RedirectResponse
    {
        $this->assertBelongsTo($property, $check);

        $data = $request->validate([
            'status' => ['required', Rule::enum(ComplianceStatus::class)],
            'cni_number' => ['nullable', 'string', 'max:32'],
        ]);

        $status = ComplianceStatus::from($data['status']);
        $cniNumber = $data['cni_number'] ?? $property->owner?->cni_number;

        if ($status === ComplianceStatus::Verified && blank($cniNumber)) {
            return back()->with('error', __('Renseignez le numéro de CNI avant de déclarer l\'identité vérifiée.'));
        }

        $property->owner?->update(['cni_number' => $data['cni_number'] ?? null]);

        $this->compliance->markStatus($check, $status, $request->user());

        return back()->with('status', __('Identité mise à jour.'));
    }

    /**
     * Diffuse un document du dossier.
     *
     * Le fichier vit sur un disque privé : il n'a pas d'URL, et cette route est
     * le seul chemin d'accès. Elle exige un administrateur authentifié et
     * vérifie que la pièce appartient bien à la villa de l'URL — sans quoi un
     * identifiant deviné suffirait à lire le dossier d'une autre villa.
     */
    public function download(Property $property, ComplianceCheck $check): StreamedResponse
    {
        $this->assertBelongsTo($property, $check);

        abort_unless($check->hasDocument(), 404);

        $disk = Storage::disk(ComplianceCheck::DISK);

        abort_unless($disk->exists($check->document_path), 404);

        // En pièce jointe, jamais affiché inline : un document déposé par un
        // propriétaire n'est jamais réencodé avant stockage, contrairement aux
        // photos. Forcer le téléchargement évite qu'un PDF piégé s'exécute
        // directement dans l'onglet de l'administrateur qui le consulte.
        return $disk->download(
            $check->document_path,
            $check->document_name ?? 'document',
            [
                // Ni cache navigateur, ni cache intermédiaire, ni indexation.
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Robots-Tag' => 'noindex, nofollow',
                'Content-Type' => $check->document_mime ?? 'application/octet-stream',
            ]
        );
    }

    public function destroyDocument(Property $property, ComplianceCheck $check, Request $request): RedirectResponse
    {
        $this->assertBelongsTo($property, $check);

        if ($check->hasDocument()) {
            Storage::disk(ComplianceCheck::DISK)->delete($check->document_path);
        }

        $check->update([
            'document_path' => null,
            'document_name' => null,
            'document_mime' => null,
            'document_size' => null,
            'status' => ComplianceStatus::Pending,
            'verified_by' => null,
            'verified_at' => null,
        ]);

        $this->compliance->syncPropertyVerification($property, $request->user());

        return back()->with('status', __('Pièce supprimée.'));
    }

    /** Empêche de lire le dossier d'une villa par l'URL d'une autre. */
    private function assertBelongsTo(Property $property, ComplianceCheck $check): void
    {
        abort_unless($check->property_id === $property->id, 404);
    }
}
