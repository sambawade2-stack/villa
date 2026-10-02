<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Photos des villas.
 *
 * Un site de location est un site de photographie : l'image est la première
 * chose que le voyageur juge, et la plus lourde à télécharger sur une
 * connexion sénégalaise. On produit donc quatre tailles en WebP, et on ne sert
 * jamais l'original.
 */
class ImageService
{
    /** Nom du dérivé => largeur en pixels. */
    public const CONVERSIONS = ['thumb' => 400, 'card' => 800, 'hero' => 1600, 'full' => 2400];

    public const DISK = 'properties';

    public const ALLOWED_MIMES = ['jpeg', 'jpg', 'png', 'webp'];

    // 50 Mo : large marge au-dessus de ce qu'un téléphone produit même en
    // pleine résolution — un plafond technique contre un envoi anormal,
    // pas une contrainte qui gênerait une vraie photo. Aucune largeur
    // minimale n'est exigée : scaleDown() ci-dessous n'agrandit jamais une
    // petite photo, elle produit simplement des dérivés plus petits.
    public const MAX_SIZE_KB = 51200;

    /**
     * Enregistre une photo et produit ses dérivés.
     *
     * L'original est conservé pour pouvoir régénérer les dérivés si les tailles
     * changent, mais il n'est jamais servi tel quel.
     */
    public function store(Property $property, UploadedFile $file): PropertyImage
    {
        $manager = new ImageManager(new Driver);
        $image = $manager->read($file->getRealPath());

        /*
         * Les données EXIF partent à l'ingestion. Une photo de villa prise au
         * téléphone contient très souvent les coordonnées GPS exactes du bien :
         * les publier annulerait toute la protection de l'adresse.
         */
        $width = $image->width();
        $height = $image->height();

        $directory = 'villas/'.$property->id;
        $name = Str::uuid()->toString();
        $disk = Storage::disk(self::DISK);

        $conversions = [];

        foreach (self::CONVERSIONS as $label => $targetWidth) {
            $variant = clone $image;

            // scaleDown : on n'agrandit jamais une petite photo, ce qui la
            // rendrait floue sans rien gagner.
            $variant->scaleDown(width: $targetWidth);

            $path = "{$directory}/{$name}-{$label}.webp";
            $disk->put($path, (string) $variant->toWebp(quality: $label === 'thumb' ? 72 : 82));

            $conversions[$label] = $path;
        }

        $originalPath = "{$directory}/{$name}-original.".$file->extension();
        $disk->put($originalPath, (string) $image->encode());

        return DB::transaction(function () use ($property, $file, $conversions, $originalPath, $width, $height) {
            $isFirst = $property->images()->count() === 0;

            return PropertyImage::create([
                'property_id' => $property->id,
                'path' => $conversions['hero'],
                'disk' => self::DISK,
                'alt' => ['fr' => $property->name, 'en' => $property->name],
                'position' => (int) $property->images()->max('position') + 1,
                // La première photo déposée devient la principale : une villa
                // sans image de couverture ne s'affiche nulle part.
                'is_primary' => $isFirst,
                'width' => $width,
                'height' => $height,
                'size' => $file->getSize(),
                'conversions' => [...$conversions, 'original' => $originalPath],
                'converted_at' => now(),
            ]);
        });
    }

    /** Désigne l'image de couverture. Une seule par villa, garanti en base. */
    public function makePrimary(PropertyImage $image): void
    {
        DB::transaction(function () use ($image) {
            /*
             * L'index unique partiel refuserait deux couvertures : on libère
             * les autres avant de désigner celle-ci.
             *
             * whereKeyNot et forceFill ne sont pas des précautions de style.
             * Si l'image visée était déjà la couverture, une mise à jour de
             * masse la passerait à false en base alors qu'elle reste true en
             * mémoire : Eloquent ne verrait aucun changement, n'écrirait rien,
             * et la villa se retrouverait sans couverture.
             */
            PropertyImage::where('property_id', $image->property_id)
                ->whereKeyNot($image->getKey())
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            $image->forceFill(['is_primary' => true])->save();
        });
    }

    /** @param  list<int>  $orderedIds */
    public function reorder(Property $property, array $orderedIds): void
    {
        DB::transaction(function () use ($property, $orderedIds) {
            foreach ($orderedIds as $position => $id) {
                $property->images()->whereKey($id)->update(['position' => $position]);
            }
        });
    }

    /**
     * Supprime une photo et ses dérivés.
     *
     * Si c'était l'image principale, la suivante prend le relais : mieux vaut
     * une couverture arbitraire qu'une villa sans visuel.
     */
    public function delete(PropertyImage $image): void
    {
        DB::transaction(function () use ($image) {
            $disk = Storage::disk($image->disk ?? self::DISK);

            foreach (($image->conversions ?? []) as $path) {
                $disk->delete($path);
            }

            $disk->delete($image->path);

            $wasPrimary = $image->is_primary;
            $propertyId = $image->property_id;

            $image->delete();

            if ($wasPrimary) {
                PropertyImage::where('property_id', $propertyId)
                    ->orderBy('position')
                    ->first()?->update(['is_primary' => true]);
            }
        });
    }
}
