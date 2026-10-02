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
 *   — conditionnel : « si applicable », l'équipe peut le marquer sans objet
 *     (RCCM, agrément touristique, autorisation d'exploitation).
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

    /**
     * L'élément s'appuie-t-il sur un document à déposer ?
     *
     * RCCM, agrément touristique et autorisation d'exploitation ne sont que
     * des numéros d'enregistrement : le champ « référence » suffit, pas de
     * fichier à conserver. Même logique pour les conditions de location,
     * qui vivent comme texte sur la fiche villa (Property::house_rules).
     */
    public function requiresDocument(): bool
    {
        return match ($this) {
            self::AddressVerified, self::PhotosVerified,
            self::BusinessRegistration, self::TourismLicence,
            self::OperatingPermit, self::RentalTerms => false,
            default => true,
        };
    }

    /**
     * Les éléments « si applicable » peuvent être écartés sans bloquer la
     * vérification.
     *
     * Le RCCM, l'agrément touristique et l'autorisation d'exploitation ont
     * rejoint cette liste après le lancement : les obtenir prend du temps
     * côté administration sénégalaise, et les exiger dès le premier jour
     * aurait bloqué la mise en ligne des toutes premières villas. Le
     * justificatif de propriété a suivi pour la même raison — demande
     * explicite malgré sa nature juridique : c'est la seule pièce qui prouve
     * le droit de louer le bien, à régulariser dès que possible.
     */
    public function isConditional(): bool
    {
        return in_array(
            $this,
            [self::OwnershipProof, self::BusinessRegistration, self::TourismLicence, self::OperatingPermit],
            strict: true,
        );
    }

    /**
     * Un document qui peut expirer doit porter une date de validité.
     *
     * Seule l'identité du propriétaire en dépend encore : les trois numéros
     * d'enregistrement n'ont plus de document, donc plus de date associée.
     */
    public function canExpire(): bool
    {
        return $this === self::OwnerIdentity;
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
