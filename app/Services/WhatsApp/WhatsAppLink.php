<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\Setting;

/**
 * Liens « click-to-chat » WhatsApp.
 *
 * Aucune dépendance à l'API : le lien ouvre l'application WhatsApp du visiteur
 * avec un message déjà rédigé. C'est le seul canal WhatsApp qui fonctionne sans
 * identifiants Meta, et il fonctionne dès aujourd'hui.
 */
final class WhatsAppLink
{
    /** Numéro de l'équipe, au format international sans séparateurs. */
    public static function number(): ?string
    {
        $number = preg_replace('/\D+/', '', (string) (Setting::get('contact.whatsapp') ?: config('whatsapp.number')));

        return $number !== '' ? $number : null;
    }

    public static function isAvailable(): bool
    {
        return self::number() !== null;
    }

    /** URL wa.me, message facultatif. */
    public static function to(?string $message = null, ?string $number = null): ?string
    {
        $number = preg_replace('/\D+/', '', (string) ($number ?? self::number()));

        if ($number === '' || $number === null) {
            return null;
        }

        $url = 'https://wa.me/'.$number;

        return $message !== null && trim($message) !== ''
            ? $url.'?text='.rawurlencode($message)
            : $url;
    }
}
