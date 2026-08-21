<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\BlockReason;
use App\Exceptions\BookingNotAllowedException;
use App\Exceptions\DatesUnavailableException;
use App\Models\AvailabilityBlock;
use App\Models\Property;
use App\Models\User;
use App\Support\PostgresErrors;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Blocages de calendrier posés à la main par l'administrateur : entretien,
 * indisponibilité, réserve pour le propriétaire.
 *
 * Distinct de BookingService, qui pose les blocages nés d'une réservation
 * (reason = booking) et les libère avec elle. Les deux écrivent dans la même
 * table et se heurtent à la même contrainte d'exclusion PostgreSQL — un
 * blocage manuel ne peut donc pas davantage chevaucher une réservation en
 * cours qu'une réservation ne peut chevaucher un autre blocage.
 */
class AvailabilityService
{
    /**
     * Pose un blocage manuel.
     *
     * @throws BookingNotAllowedException si les dates sont incohérentes
     * @throws DatesUnavailableException si elles chevauchent un blocage ou une réservation existants
     */
    public function block(
        Property $property,
        string $startsOn,
        string $endsOn,
        BlockReason $reason,
        ?string $note,
        ?User $createdBy,
    ): AvailabilityBlock {
        $this->assertOrdered($startsOn, $endsOn);

        /*
         * DB::transaction() est nécessaire ici, pas seulement une bonne
         * pratique : sans elle, l'échec de l'INSERT laisse la transaction
         * englobante avortée (PostgreSQL refuse toute requête suivante tant
         * qu'elle n'est pas annulée). Sous un test, qui enveloppe déjà tout
         * dans une transaction, cela ferait échouer la moindre requête après
         * le catch. La transaction imbriquée pose un SAVEPOINT auquel revenir
         * proprement — le même mécanisme que BookingService::blockDates().
         */
        try {
            return DB::transaction(fn () => AvailabilityBlock::create([
                'property_id' => $property->id,
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'reason' => $reason,
                'note' => $note,
                'created_by' => $createdBy?->id,
            ]));
        } catch (QueryException $e) {
            if (PostgresErrors::isExclusionViolation($e)) {
                throw new DatesUnavailableException(
                    __('Ces dates chevauchent un blocage ou une réservation déjà en place.')
                );
            }

            throw $e;
        }
    }

    /**
     * Retire un blocage.
     *
     * Un blocage né d'une réservation ne se retire pas ici : il faut annuler
     * la réservation, qui le libère elle-même — sans quoi les dates seraient
     * rendues disponibles alors qu'un client a toujours payé pour elles.
     *
     * @throws BookingNotAllowedException si le blocage provient d'une réservation
     */
    public function unblock(AvailabilityBlock $block): void
    {
        if ($block->reason === BlockReason::Booking) {
            throw new BookingNotAllowedException(
                __('Ce blocage provient d\'une réservation : annulez la réservation pour libérer ces dates.')
            );
        }

        $block->delete();
    }

    /**
     * daterange() lève sa propre erreur (22000) si la fin précède le début, et
     * la contrainte CHECK refuse une période de durée nulle (23514) — deux
     * SQLSTATE différents pour une même faute de saisie. Un contrôle unique
     * ici évite de les distinguer côté formulaire.
     *
     * @throws BookingNotAllowedException
     */
    private function assertOrdered(string $startsOn, string $endsOn): void
    {
        if (Carbon::parse($endsOn)->lte(Carbon::parse($startsOn))) {
            throw new BookingNotAllowedException(__('La date de fin doit suivre la date de début.'));
        }
    }
}
