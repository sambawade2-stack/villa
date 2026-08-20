<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;

enum MessageChannel: string
{
    use HasLabel;

    /** Messagerie interne du site. */
    case InApp = 'in_app';

    case WhatsApp = 'whatsapp';

    public function icon(): string
    {
        return match ($this) {
            self::InApp => 'message-circle',
            self::WhatsApp => 'phone',
        };
    }
}
