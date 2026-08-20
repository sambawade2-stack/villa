<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Fabrique les visuels des données de démonstration.
 *
 * Ce ne sont volontairement PAS des photographies : ce sont des dégradés
 * abstraits générés localement avec GD, dérivés du nom de la villa. Ils
 * permettent d'exercer réellement la chaîne image — stockage, dérivés,
 * srcset, mise en page — sans faire passer une image d'emprunt pour une
 * photo de villa. Les vraies photos seront chargées par l'administrateur.
 */
final class DemoImageFactory
{
    /** Dérivés produits : nom => largeur. La hauteur suit un ratio 3:2. */
    private const CONVERSIONS = ['thumb' => 400, 'card' => 800, 'hero' => 1600];

    /**
     * @return array{path: string, conversions: array<string, string>, width: int, height: int, size: int}
     */
    public function make(string $seed, int $index, string $directory = 'demo'): array
    {
        // La graine rend le rendu reproductible : reseeder redonne les mêmes visuels.
        $hash = crc32($seed.'#'.$index);
        $base = $this->paint($hash);

        $disk = Storage::disk('properties');
        $conversions = [];
        $heroSize = 0;

        foreach (self::CONVERSIONS as $name => $width) {
            $height = (int) round($width / 1.5);
            $scaled = imagescale($base, $width, $height, IMG_BICUBIC);

            ob_start();
            imagejpeg($scaled, null, $name === 'thumb' ? 78 : 84);
            $binary = (string) ob_get_clean();
            imagedestroy($scaled);

            $path = sprintf('%s/%s-%d-%s.jpg', $directory, substr(md5($seed), 0, 10), $index, $name);
            $disk->put($path, $binary);

            $conversions[$name] = $path;

            if ($name === 'hero') {
                $heroSize = strlen($binary);
            }
        }

        imagedestroy($base);

        return [
            'path' => $conversions['hero'],
            'conversions' => $conversions,
            'width' => self::CONVERSIONS['hero'],
            'height' => (int) round(self::CONVERSIONS['hero'] / 1.5),
            'size' => $heroSize,
        ];
    }

    /**
     * Peint une petite image — ciel dégradé, disque solaire, mer — puis on
     * l'agrandit. Calculer 26 000 pixels en PHP est instantané ; en calculer
     * 1,7 million ne le serait pas.
     */
    private function paint(int $hash): \GdImage
    {
        [$w, $h] = [240, 160];
        $image = imagecreatetruecolor($w, $h);

        // Teinte dominante tirée de la graine, mais bornée aux ambiances
        // de la Petite Côte : couchers de soleil, océan, sable.
        $hue = ($hash % 60) / 360 + (($hash >> 8) % 2 ? 0.55 : 0.02);
        $horizon = (int) round($h * (0.58 + (($hash >> 4) % 10) / 100));

        for ($y = 0; $y < $h; $y++) {
            $isSky = $y < $horizon;
            $t = $isSky ? $y / max(1, $horizon) : ($y - $horizon) / max(1, $h - $horizon);

            if ($isSky) {
                [$r, $g, $b] = $this->hsl($hue, 0.42 - 0.20 * $t, 0.30 + 0.42 * $t);
            } else {
                [$r, $g, $b] = $this->hsl($hue + 0.5, 0.30, 0.34 - 0.16 * $t);
            }

            $colour = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $w, $y, $colour);
        }

        // Disque solaire, posé au-dessus de l'horizon.
        $sunX = 40 + ($hash % ($w - 80));
        $sunY = (int) round($horizon * (0.30 + (($hash >> 12) % 30) / 100));
        [$r, $g, $b] = $this->hsl($hue + 0.03, 0.55, 0.80);
        $sun = imagecolorallocatealpha($image, $r, $g, $b, 35);
        imagefilledellipse($image, $sunX, $sunY, 26, 26, $sun);

        // Reflet sur l'eau, sous le disque.
        for ($i = 0; $i < 7; $i++) {
            $reflect = imagecolorallocatealpha($image, $r, $g, $b, 90 + $i * 5);
            imagefilledrectangle(
                $image,
                $sunX - 14 + $i, $horizon + 3 + $i * 4,
                $sunX + 14 - $i, $horizon + 5 + $i * 4,
                $reflect
            );
        }

        imagefilter($image, IMG_FILTER_SMOOTH, 8);

        return $image;
    }

    /** @return array{int, int, int} */
    private function hsl(float $h, float $s, float $l): array
    {
        $h = fmod($h, 1.0);
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h * 6, 2) - 1));
        $m = $l - $c / 2;

        [$r, $g, $b] = match ((int) floor($h * 6)) {
            0 => [$c, $x, 0.0],
            1 => [$x, $c, 0.0],
            2 => [0.0, $c, $x],
            3 => [0.0, $x, $c],
            4 => [$x, 0.0, $c],
            default => [$c, 0.0, $x],
        };

        return [
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255),
        ];
    }
}
