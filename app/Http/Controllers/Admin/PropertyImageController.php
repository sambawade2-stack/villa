<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Services\Media\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PropertyImageController extends Controller
{
    public function __construct(private readonly ImageService $images) {}

    public function store(Request $request, Property $property): RedirectResponse
    {
        $request->validate([
            'photos' => ['required', 'array', 'max:20'],
            'photos.*' => [
                'file', 'image',
                'mimes:'.implode(',', ImageService::ALLOWED_MIMES),
                'max:'.ImageService::MAX_SIZE_KB,
                'dimensions:min_width='.ImageService::MIN_WIDTH,
            ],
        ], [
            'photos.*.dimensions' => __('Chaque photo doit faire au moins :width pixels de large.', [
                'width' => ImageService::MIN_WIDTH,
            ]),
            // Message dédié plutôt qu'un nom de champ substitué dans le
            // gabarit générique : « la photo » précédé de « du champ » ne se
            // lit pas comme une phrase — un nom de champ suppose un nom
            // commun sans article, pas un groupe nominal complet.
            'photos.*.uploaded' => __('Cette photo n\'a pas pu être envoyée. Réessayez, ou choisissez un fichier plus léger.'),
            // Sans ce message, l'échec générique affiche « Le champ photos.0
            // doit être une image » — illisible pour un administrateur. La
            // cause la plus fréquente : une photo iPhone au format HEIC, que
            // ni la règle "image" ni "mimes" ne reconnaissent.
            'photos.*.image' => __('Ce fichier n\'est pas reconnu comme une image. Si la photo vient d\'un iPhone au format HEIC, exportez-la d\'abord en JPEG (dans l\'appli Photos : partager la photo, puis « Options » → « Le plus compatible »).'),
            'photos.*.mimes' => __('Format non accepté : seuls les fichiers JPEG, PNG ou WebP peuvent être envoyés.'),
            'photos.*.max' => __('Cette photo dépasse :max Mo. Compressez-la ou choisissez un fichier plus léger.', [
                'max' => (int) (ImageService::MAX_SIZE_KB / 1024),
            ]),
        ]);

        foreach ($request->file('photos') as $file) {
            $this->images->store($property, $file);
        }

        return back()->with('status', trans_choice(
            ':count photo ajoutée.|:count photos ajoutées.',
            count($request->file('photos')),
            ['count' => count($request->file('photos'))],
        ));
    }

    public function makePrimary(Property $property, PropertyImage $image): RedirectResponse
    {
        $this->assertBelongsTo($property, $image);

        $this->images->makePrimary($image);

        return back()->with('status', __('Image de couverture mise à jour.'));
    }

    public function reorder(Request $request, Property $property): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        // Même garde qu'ailleurs dans ce contrôleur : un identifiant étranger
        // à cette villa refuse plutôt que de disparaître en silence dans la
        // mise à jour scopée par la relation.
        abort_unless(
            $property->images()->whereIn('id', $data['order'])->count() === count($data['order']),
            404
        );

        $this->images->reorder($property, $data['order']);

        return response()->json(['ok' => true]);
    }

    public function destroy(Property $property, PropertyImage $image): RedirectResponse
    {
        $this->assertBelongsTo($property, $image);

        $this->images->delete($image);

        return back()->with('status', __('Photo supprimée.'));
    }

    /** Empêche d'agir sur la photo d'une villa via l'URL d'une autre. */
    private function assertBelongsTo(Property $property, PropertyImage $image): void
    {
        abort_unless($image->property_id === $property->id, 404);
    }
}
