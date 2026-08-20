<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Exécute une écriture censée violer une contrainte PostgreSQL et renvoie
 * le SQLSTATE, ou null si elle est passée.
 *
 * Le passage par une transaction imbriquée est indispensable : sous
 * RefreshDatabase les tests tournent déjà dans une transaction, et une erreur
 * PostgreSQL la place en état « aborted ». La transaction imbriquée pose un
 * SAVEPOINT, si bien que l'échec attendu n'empêche pas les assertions suivantes.
 *
 * @param  Closure(): void  $write
 */
function sqlStateOf(Closure $write): ?string
{
    try {
        DB::transaction($write);

        return null;
    } catch (QueryException $e) {
        return (string) ($e->errorInfo[0] ?? 'unknown');
    }
}

/** Code SQLSTATE d'une violation de contrainte d'exclusion. */
const EXCLUSION_VIOLATION = '23P01';

/** Code SQLSTATE d'une violation de contrainte CHECK. */
const CHECK_VIOLATION = '23514';

/** Code SQLSTATE d'une violation d'unicité. */
const UNIQUE_VIOLATION = '23505';

/**
 * Code SQLSTATE d'une donnée invalide.
 *
 * Des dates inversées échouent ici, et non sur le CHECK : la colonne générée
 * `period` est calculée avant que les contraintes ne soient évaluées, et
 * daterange() refuse lui-même une borne basse supérieure à la borne haute.
 */
const DATA_EXCEPTION = '22000';
