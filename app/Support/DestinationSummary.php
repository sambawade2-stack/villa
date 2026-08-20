<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Destination;

/**
 * Destination telle qu'elle s'affiche sur l'accueil : la destination, son
 * nombre de villas publiées, et les URL de son visuel.
 *
 * Un objet plutôt qu'un tableau associatif : les vues n'ont plus à deviner les
 * clés, et l'analyse statique suit le type de bout en bout.
 */
final readonly class DestinationSummary
{
    public function __construct(
        public Destination $destination,
        public int $count,
        public ?string $imageUrl = null,
        public ?string $srcset = null,
    ) {}
}
