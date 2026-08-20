<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Compte administrateur du jeu de démonstration
    |--------------------------------------------------------------------------
    |
    | Lu par DemoSeeder afin qu'un `migrate:fresh --seed` ne réécrase pas vos
    | identifiants. Les valeurs réelles vivent dans .env, qui n'est pas versionné :
    | un mot de passe d'administration n'a rien à faire dans un dépôt.
    |
    */

    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@petitecotevillas.test'),
        'password' => env('ADMIN_PASSWORD', 'password'),
        'first_name' => env('ADMIN_FIRST_NAME', 'Aïssatou'),
        'last_name' => env('ADMIN_LAST_NAME', 'Diagne'),
    ],

    'customer_demo' => [
        'email' => env('DEMO_CUSTOMER_EMAIL', 'client@petitecotevillas.test'),
        'password' => env('DEMO_CUSTOMER_PASSWORD', 'password'),
    ],

];
