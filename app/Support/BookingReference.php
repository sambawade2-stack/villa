<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Référence de réservation, forme PCV-2026-000042.
 *
 * Générateur unique de l'application. Il a existé un temps deux compteurs —
 * celui-ci et un compteur local dans le seeder de démonstration — et ils
 * produisaient les mêmes numéros : toute réservation créée après un seeding
 * échouait sur la contrainte d'unicité. Le nombre n'a qu'une source, la
 * séquence PostgreSQL, atomique sous concurrence.
 */
final class BookingReference
{
    public const SEQUENCE = 'booking_reference_seq';

    public static function next(): string
    {
        $number = (int) DB::selectOne('SELECT nextval(?) AS n', [self::SEQUENCE])->n;

        return sprintf('PCV-%s-%06d', now()->year, $number);
    }

    /**
     * Recale la séquence au-dessus des références déjà en base.
     *
     * Indispensable après un import ou une restauration : sans cela, la
     * séquence redistribuerait des numéros déjà attribués.
     */
    public static function syncSequence(): void
    {
        $highest = (int) DB::selectOne(
            "SELECT coalesce(max(split_part(reference, '-', 3)::int), 0) AS n FROM bookings"
        )->n;

        DB::statement('SELECT setval(?, ?, true)', [self::SEQUENCE, max($highest, 1)]);
    }
}
