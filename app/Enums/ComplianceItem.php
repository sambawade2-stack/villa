<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasLabel;

/**
 * Pièces du dossier de conformité d'une villa.
 *
 * Deux natures d'éléments :
 *   — ceux qui reposent sur un document déposé (identité, titre, RCCM…) ;
 *   — ceux qui reposent sur un contrôle mené par l'équipe (adresse, photos).
 *
 * Deux niveaux d'exigence :
 *   — requis : la villa ne peut pas être déclarée vérifiée sans ;
 *   — conditionnel : « si applicable », l'équipe peut le marquer sans objet.
 */
enum ComplianceItem: string
{
    use HasLabel;

    case OwnerIdentity = 'owner_identity';
    case OwnershipProof = 'ownership_proof';
    case BusinessRegistration = 'business_registration';
    case TourismLicence = 'tourism_licence';
    case OperatingPermit = 'operating_permit';
    case AddressVerified = 'address_verified';
    case PhotosVerified = 'photos_verified';
    case RentalTerms = 'rental_terms';

    /** L'élément s'appuie-t-il sur un document à déposer ? */
    public function requiresDocument(): bool
    {
        return match ($this) {
            self::AddressVerified, self::PhotosVerified => false,
            default => true,
        };
    }

    /** Les éléments « si applicable » peuvent être écartés sans bloquer la vérification. */
    public function isConditional(): bool
    {
        return in_array($this, [self::TourismLicence, self::OperatingPermit], strict: true);
    }

    /** Un document qui peut expirer doit porter une date de validité. */
    public function canExpire(): bool
    {
        return in_array($this, [
            self::OwnerIdentity, self::TourismLicence, self::OperatingPermit, self::BusinessRegistration,
        ], strict: true);
    }

    public function icon(): string
    {
        return match ($this) {
            self::OwnerIdentity => 'users',
            self::OwnershipProof => 'key',
            self::BusinessRegistration => 'home',
            self::TourismLicence => 'badge-check',
            self::OperatingPermit => 'shield-check',
            self::AddressVerified => 'map-pin',
            self::PhotosVerified => 'eye',
            self::RentalTerms => 'info',
        };
    }

    /**
     * Ordre d'affichage du dossier, tel que défini par l'exploitant.
     *
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [
            self::OwnerIdentity,
            self::OwnershipProof,
            self::BusinessRegistration,
            self::TourismLicence,
            self::OperatingPermit,
            self::AddressVerified,
            self::PhotosVerified,
            self::RentalTerms,
        ];
    }
}
