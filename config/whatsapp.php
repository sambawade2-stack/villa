<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Lien direct (click-to-chat)
    |--------------------------------------------------------------------------
    |
    | Ouvre WhatsApp avec un message pré-rédigé. Ne demande aucun compte
    | développeur, aucune clé : c'est ce que font la plupart des entreprises
    | sénégalaises, et c'est opérationnel dès le premier jour.
    |
    | Le numéro est au format international, sans « + » ni espaces.
    |
    */

    'number' => preg_replace('/\D+/', '', (string) env('WHATSAPP_NUMBER', '')),

    /*
    |--------------------------------------------------------------------------
    | Passerelle applicative
    |--------------------------------------------------------------------------
    |
    | « log »   : écrit le message dans les journaux. Défaut en développement,
    |             et seul mode réellement vérifiable sans identifiants Meta.
    | « cloud » : WhatsApp Cloud API de Meta. Exige un compte WhatsApp Business,
    |             un identifiant de numéro et un jeton permanent.
    |
    */

    'driver' => env('WHATSAPP_DRIVER', 'log'),

    'cloud' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        // Jeton que Meta renvoie à la vérification de l'URL du webhook.
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        // Secret de l'application, pour valider la signature X-Hub-Signature-256.
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        'timeout' => 15,
    ],

];
