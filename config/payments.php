<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Passerelle par défaut
    |--------------------------------------------------------------------------
    |
    | « manual » : règlement hors ligne (Wave, Orange Money, virement), confirmé
    | par l'administrateur. Seul mode opérationnel à ce stade.
    |
    | PayDunya, PayTech et Stripe rejoindront cette liste à l'étape 09 ; leurs
    | identifiants iront dans .env et nulle part ailleurs.
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'manual'),

    'manual' => [
        // Coordonnées communiquées au client, éditables dans /admin/parametres.
        'instructions_setting' => 'payment.manual_instructions',
    ],

];
