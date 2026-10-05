<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\OwnerStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PropertyOwner;
use App\Services\Owner\OwnerAccountService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Propriétaires.
 *
 * En v1 ce sont des contacts internes, sans compte : rien ici n'est visible du
 * public, et le téléphone comme l'adresse ne quittent pas cet écran.
 */
class PropertyOwnerController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.owners.index', [
            'owners' => PropertyOwner::query()
                ->withCount('properties')
                ->search($request->query('q'))
                ->orderBy('last_name')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function show(PropertyOwner $owner): View
    {
        $owner->load(['properties.destination', 'properties.primaryImage']);

        $revenue = (int) Booking::query()
            ->whereIn('property_id', $owner->properties->pluck('id'))
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->sum('total_amount');

        return view('admin.owners.show', [
            'owner' => $owner,
            'bookingsCount' => Booking::query()->whereIn('property_id', $owner->properties->pluck('id'))->count(),
            'revenue' => Money::from($revenue),
            'payout' => Money::from((int) $owner->commissions()->sum('owner_payout_amount')),
        ]);
    }

    public function create(): View
    {
        return view('admin.owners.form', ['owner' => new PropertyOwner]);
    }

    public function store(Request $request): RedirectResponse
    {
        $owner = PropertyOwner::create($this->validated($request));

        return redirect()->route('admin.owners.show', $owner)
            ->with('status', __('Propriétaire enregistré.'));
    }

    public function edit(PropertyOwner $owner): View
    {
        return view('admin.owners.form', compact('owner'));
    }

    public function update(Request $request, PropertyOwner $owner): RedirectResponse
    {
        $owner->update($this->validated($request, $owner));

        return redirect()->route('admin.owners.show', $owner)
            ->with('status', __('Fiche mise à jour.'));
    }

    /**
     * Ouvre l'accès au portail propriétaire.
     *
     * Pour plus de transparence, un propriétaire suit lui-même l'état de sa
     * villa et bloque ses propres dates, plutôt que de dépendre entièrement
     * de l'administrateur.
     */
    public function grantAccess(PropertyOwner $owner, OwnerAccountService $accounts): RedirectResponse
    {
        try {
            $accounts->grantAccess($owner);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __(
            'Accès activé. Un e-mail a été envoyé à :email pour choisir un mot de passe.',
            ['email' => $owner->email]
        ));
    }

    /**
     * Suppression définitive : la fiche propriétaire et son éventuel compte
     * de portail disparaissent réellement de la base, sans retour possible.
     *
     * La contrainte `restrictOnDelete()` sur properties.property_owner_id
     * empêche déjà la suppression si des villas (même archivées) lui sont
     * encore rattachées — on se contente de transformer cette erreur SQL en
     * message compréhensible plutôt que de la vérifier nous-mêmes en amont.
     */
    public function destroy(PropertyOwner $owner): RedirectResponse
    {
        try {
            DB::transaction(function () use ($owner) {
                // forceDelete et non delete : un compte seulement "soft deleted"
                // garderait son e-mail en base et bloquerait toute réouverture
                // d'accès future avec cette même adresse (le contrôle anti-
                // doublon de OwnerAccountService::grantAccess porte volontairement
                // sur les comptes supprimés aussi, pas seulement les actifs).
                $owner->user?->forceDelete();
                $owner->forceDelete();
            });
        } catch (QueryException) {
            return back()->with('error', __(
                'Impossible de supprimer : ce propriétaire a encore des villas enregistrées. Retirez-les ou réattribuez-les d\'abord.'
            ));
        }

        return redirect()->route('admin.owners.index')->with('status', __('Propriétaire supprimé définitivement.'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?PropertyOwner $owner = null): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:32'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:190',
                Rule::unique('property_owners', 'email')->ignore($owner?->id)->whereNull('deleted_at')],
            'city' => ['nullable', 'string', 'max:120'],
            'internal_address' => ['nullable', 'string', 'max:500'],
            'internal_notes' => ['nullable', 'string', 'max:4000'],
            'status' => ['required', Rule::enum(OwnerStatus::class)],
        ]);
    }
}
