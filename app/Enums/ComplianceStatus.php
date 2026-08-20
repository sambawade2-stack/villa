<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;

enum ComplianceStatus: string
{
    use HasLabel;

    /** Rien n'a encore été fourni. */
    case Pending = 'pending';

    /** Une pièce est déposée, mais l'équipe ne l'a pas encore contrôlée. */
    case Provided = 'provided';

    /** Contrôlée et acceptée. */
    case Verified = 'verified';

    /** Sans objet pour cette villa — réservé aux éléments conditionnels. */
    case NotApplicable = 'not_applicable';

    /** Contrôlée et refusée : la pièce doit être remplacée. */
    case Rejected = 'rejected';

    /** Acceptée par le passé, mais la date de validité est dépassée. */
    case Expired = 'expired';

    /** L'élément est-il en règle, du point de vue du dossier complet ? */
    public function satisfies(): bool
    {
        return in_array($this, [self::Verified, self::NotApplicable], strict: true);
    }

    /** L'élément appelle-t-il une action de l'équipe ? */
    public function needsAttention(): bool
    {
        return in_array($this, [self::Rejected, self::Expired], strict: true);
    }

    public function color(): string
    {
        return match ($this) {
            self::Verified => 'success',
            self::NotApplicable => 'neutral',
            self::Provided => 'warning',
            self::Pending => 'neutral',
            self::Rejected, self::Expired => 'danger',
        };
    }
}
