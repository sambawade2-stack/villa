<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** Règle métier non respectée : dates passées, séjour trop court, capacité dépassée… */
class BookingNotAllowedException extends RuntimeException {}
