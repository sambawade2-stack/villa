<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Symfony pose « Accept-Language: en-us » par défaut dans les requêtes
         * fabriquées. Nos tests décrivent un visiteur francophone — le marché
         * de la plateforme — sauf quand ils en disent autrement. Sans cet
         * en-tête, la négociation de langue servirait l'anglais partout et les
         * assertions porteraient sur des textes que personne ne verra.
         */
        $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9');
    }
}
