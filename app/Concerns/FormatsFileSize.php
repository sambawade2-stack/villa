<?php

declare(strict_types=1);

namespace App\Concerns;

/**
 * Taille de fichier lisible.
 *
 * Partagée par PropertyImage et ComplianceCheck : les deux portent une colonne
 * de taille en octets et l'affichent en administration.
 */
trait FormatsFileSize
{
    /**
     * Colonne portant la taille en octets.
     *
     * Une méthode plutôt qu'une propriété : redéclarer une propriété de trait
     * avec une autre valeur par défaut est refusé par PHP, une méthode se
     * surcharge sans réserve.
     */
    protected function fileSizeColumn(): string
    {
        return 'size';
    }

    public function humanSize(): ?string
    {
        $size = $this->getAttribute($this->fileSizeColumn());

        if ($size === null) {
            return null;
        }

        $units = ['o', 'ko', 'Mo'];
        $value = (float) $size;
        $unit = 0;

        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        return number_format($value, $unit === 0 ? 0 : 1, ',', "\u{202F}").' '.$units[$unit];
    }
}
