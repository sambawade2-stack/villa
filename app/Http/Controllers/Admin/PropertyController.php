<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Models\Amenity;
use App\Models\Destination;
use App\Models\Property;
use App\Models\PropertyOwner;
use App\Services\Compliance\ComplianceService;
use App\Services\Media\ImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $properties = Property::query()
            ->with(['destination:id,name,slug', 'owner:id,first_name,last_name', 'primaryImage'])
            ->withCount([
                'complianceChecks as compliance_satisfied_count' => fn ($q) => $q->satisfied(),
                'complianceChecks as compliance_alert_count' => fn ($q) => $q->needingAttention(),
            ])
            ->when($status !== null && in_array($status, PropertyStatus::values(), true),
                fn ($q) => $q->where('status', $status))
            ->search($request->query('q'))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.villas.index', [
            'properties' => $properties,
            'status' => $status,
            'counts' => Property::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('admin.villas.create', $this->formOptions());
    }

    /**
     * Crée la villa en brouillon.
     *
     * On ne demande ici que l'indispensable. Le reste — description, photos,
     * tarifs, équipements — s'ajoute ensuite section par section : un
     * formulaire de trente champs fait perdre toute la saisie au premier oubli.
     */
    public function store(StorePropertyRequest $request, ComplianceService $compliance): RedirectResponse
    {
        $data = $request->validated();

        // 'status' n'est pas mass-assignable : forceCreate est le seul chemin
        // qui puisse l'écrire, ici en toute sécurité puisque $data ne contient
        // que des champs validés par StorePropertyRequest.
        $property = Property::forceCreate([
            ...$data,
            'slug' => $this->uniqueSlug($data['name']),
            'status' => PropertyStatus::Draft,
            'beds' => $data['bedrooms'],
            'min_nights' => 1,
        ]);

        // Le dossier de conformité naît avec la villa : il n'y a pas de moment
        // plus tardif où l'on penserait à le créer.
        $compliance->ensureChecklist($property);

        return redirect()->route('admin.villas.edit', $property)
            ->with('status', __('Brouillon créé. Complétez la fiche, puis publiez-la.'));
    }

    public function edit(Property $property): View
    {
        $property->load([
            'amenities', 'images', 'destination', 'owner',
            'pricingRules' => fn ($q) => $q->orderBy('starts_on'),
            'availabilityBlocks' => fn ($q) => $q->orderBy('starts_on'),
        ]);

        return view('admin.villas.edit', [
            'property' => $property,
            'blockers' => $property->publicationBlockers(),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdatePropertyRequest $request, Property $property): RedirectResponse
    {
        $data = $request->validated();

        $property->update([
            ...collect($data)->except([
                'description_fr', 'description_en',
                'short_description_fr', 'short_description_en',
                'house_rules_fr', 'house_rules_en',
                'amenities',
            ])->all(),
            'description' => ['fr' => $data['description_fr'] ?? null, 'en' => $data['description_en'] ?? null],
            'short_description' => ['fr' => $data['short_description_fr'] ?? null, 'en' => $data['short_description_en'] ?? null],
            'house_rules' => ['fr' => $data['house_rules_fr'] ?? null, 'en' => $data['house_rules_en'] ?? null],
            'pets_allowed' => $request->boolean('pets_allowed'),
            'parties_allowed' => $request->boolean('parties_allowed'),
            'smoking_allowed' => $request->boolean('smoking_allowed'),
        ]);

        $property->amenities()->sync($data['amenities'] ?? []);

        return back()->with('status', __('Fiche enregistrée.'));
    }

    /**
     * Publie la villa.
     *
     * Le refus s'appuie sur publicationBlockers(), qui liste les manques : nom,
     * description, photo, prix, capacité. On ne publie jamais une fiche
     * incomplète — elle décevrait le voyageur avant même sa réservation.
     */
    public function publish(Property $property): RedirectResponse
    {
        $blockers = $property->publicationBlockers();

        if ($blockers !== []) {
            return back()->with('error', __('Publication impossible : il manque encore :manques.', [
                'manques' => implode(', ', array_map(fn (string $key) => __('villas.blockers.'.$key), $blockers)),
            ]));
        }

        $property->forceFill([
            'status' => PropertyStatus::Published,
            'published_at' => $property->published_at ?? now(),
            ...$this->approximateCoordinates($property),
        ])->save();

        return back()->with('status', __('Villa publiée.'));
    }

    public function unpublish(Request $request, Property $property): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:unpublished,suspended'],
        ]);

        $property->forceFill(['status' => PropertyStatus::from($data['status'])])->save();

        return back()->with('status', __('Villa retirée du catalogue.'));
    }

    public function destroy(Property $property): RedirectResponse
    {
        if ($property->bookings()->holdingDates()->exists()) {
            return back()->with('error', __('Cette villa porte des réservations en cours : annulez-les d\'abord.'));
        }

        $property->delete();

        return redirect()->route('admin.villas.index')
            ->with('status', __('Villa supprimée. Elle reste récupérable en base.'));
    }

    public function trashed(): View
    {
        return view('admin.villas.trashed', [
            'properties' => Property::onlyTrashed()
                ->with(['destination:id,name', 'owner:id,first_name,last_name'])
                ->orderByDesc('deleted_at')
                ->paginate(20),
        ]);
    }

    public function restore(int $property): RedirectResponse
    {
        Property::onlyTrashed()->findOrFail($property)->restore();

        return back()->with('status', __('Villa restaurée. Elle est de retour au catalogue (non publiée).'));
    }

    /**
     * Purge définitive, depuis la corbeille uniquement.
     *
     * La contrainte `restrictOnDelete()` sur bookings.property_id protège déjà
     * l'historique de réservation : impossible de supprimer pour de bon une
     * villa qui en porte, même anciennes. Les photos et pièces du dossier de
     * conformité sont effacées explicitement via leurs modèles (et non
     * laissées à la cascade SQL) car ce sont les seules à avoir un fichier sur
     * disque à nettoyer en même temps que leur ligne.
     */
    public function forceDestroy(int $property, ImageService $images): RedirectResponse
    {
        $villa = Property::onlyTrashed()->findOrFail($property);

        try {
            $villa->images->each($images->delete(...));
            $villa->complianceChecks->each(fn ($check) => $check->delete());
            $villa->forceDelete();
        } catch (QueryException) {
            return back()->with('error', __(
                'Impossible de supprimer définitivement : cette villa porte des réservations enregistrées.'
            ));
        }

        return redirect()->route('admin.villas.trashed')->with('status', __('Villa supprimée définitivement.'));
    }

    /**
     * Coordonnées floutées servies au public.
     *
     * Environ 400 à 600 mètres de décalage : assez pour situer le quartier,
     * pas assez pour désigner la maison. Les coordonnées exactes ne quittent
     * jamais l'administration.
     *
     * @return array<string, float|null>
     */
    private function approximateCoordinates(Property $property): array
    {
        if ($property->latitude === null || $property->longitude === null) {
            return [];
        }

        // Décalage déterministe : la position floutée ne bouge pas à chaque
        // publication, ce qui ferait sauter le point sur la carte.
        $seed = crc32($property->slug);
        $offset = fn (int $shift) => ((($seed >> $shift) % 80) - 40) / 10000;

        return [
            'approx_latitude' => round((float) $property->latitude + $offset(0), 7),
            'approx_longitude' => round((float) $property->longitude + $offset(8), 7),
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Property::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'owners' => PropertyOwner::query()->active()->orderBy('last_name')->get(),
            'destinations' => Destination::query()->ordered()->get(),
            'types' => PropertyType::cases(),
            'amenities' => Amenity::query()->active()->ordered()->get()->groupBy('category'),
        ];
    }
}
