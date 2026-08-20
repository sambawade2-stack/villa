<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;

enum PropertyStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Published = 'published';
    case Unpublished = 'unpublished';
    case Suspended = 'suspended';

    /** Seul « published » est visible du public. */
    public function isPublic(): bool
    {
        return $this === self::Published;
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Published => 'success',
            self::Unpublished => 'warning',
            self::Suspended => 'danger',
        };
    }
}
