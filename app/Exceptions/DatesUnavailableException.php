<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Les dates demandées viennent d'être prises.
 *
 * Levée quand PostgreSQL refuse le blocage de calendrier (SQLSTATE 23P01).
 * C'est le cas de course réel : deux visiteurs valident la même villa aux mêmes
 * dates à la même seconde. Le premier passe, le second reçoit ceci.
 */
class DatesUnavailableException extends RuntimeException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message !== '' ? $message : __('Ces dates viennent d\'être réservées. Choisissez d\'autres dates.'));
    }
}
