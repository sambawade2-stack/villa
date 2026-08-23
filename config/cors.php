<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Publié et restreint dès maintenant plutôt qu'au moment où une API
    | verra le jour : le défaut du framework (origine « * ») est sans risque
    | tant qu'aucune route api/* n'existe, mais devient permissif dès la
    | première. Autant fixer l'origine avant que la question ne se pose dans
    | l'urgence.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [env('APP_URL', 'http://localhost')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
