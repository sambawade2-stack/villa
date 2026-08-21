<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\QueryException;

/**
 * Reconnaît les codes d'erreur PostgreSQL sur lesquels le domaine métier agit.
 *
 * Centralisé parce que deux services distincts — la tenue d'une réservation et
 * le blocage manuel d'un calendrier — retombent tous deux sur la même
 * contrainte d'exclusion et doivent la traduire de la même façon.
 */
final class PostgresErrors
{
    /** Violation d'une contrainte EXCLUDE : chevauchement refusé par la base. */
    private const EXCLUSION_VIOLATION = '23P01';

    public static function isExclusionViolation(QueryException $e): bool
    {
        return ($e->errorInfo[0] ?? null) === self::EXCLUSION_VIOLATION;
    }
}
