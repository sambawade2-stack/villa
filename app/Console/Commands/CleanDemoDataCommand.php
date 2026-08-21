<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\Commission;
use App\Models\ComplianceCheck;
use App\Models\Conversation;
use App\Models\Favorite;
use App\Models\Message;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\PricingRule;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyOwner;
use App\Models\Refund;
use App\Models\Review;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Retire tout le contenu de démonstration : villas, propriétaires, clients
 * fictifs, réservations et tout ce qui en dépend.
 *
 * Conservé volontairement : le compte administrateur, les destinations et
 * les équipements (données de référence réelles), et les réglages. C'est ce
 * qui reste quand on veut repartir d'un catalogue vide pour y saisir de
 * vraies villas.
 *
 * L'ordre des suppressions respecte les contraintes de clé étrangère : les
 * enfants d'abord (paiements, avis, blocages…), les villas et propriétaires
 * ensuite. S'appuyer sur les seules cascades de la base aurait fonctionné
 * pour la plupart des tables, mais bookings.property_id et
 * properties.property_owner_id sont en restrictOnDelete — la base refuserait
 * la suppression si l'ordre n'était pas le bon.
 */
class CleanDemoDataCommand extends Command
{
    protected $signature = 'demo:clean {--force : Ignore la confirmation}';

    protected $description = 'Supprime tout le contenu de démonstration (villas, propriétaires, clients, réservations)';

    public function handle(): int
    {
        $counts = $this->currentCounts();

        $this->table(['Table', 'Lignes à supprimer'], collect($counts)->map(
            fn (int $n, string $label) => [$label, $n]
        )->values()->all());

        if (array_sum($counts) === 0) {
            $this->info('Rien à nettoyer : aucune donnée de démonstration en base.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(
            'Supprimer définitivement ces données ? Le compte administrateur, les destinations, '
            .'les équipements et les réglages seront conservés.'
        )) {
            $this->comment('Annulé.');

            return self::SUCCESS;
        }

        DB::transaction(function () {
            // Ce qui dépend d'une réservation.
            PaymentTransaction::query()->delete();
            Refund::query()->delete();
            Payment::query()->delete();
            Commission::query()->delete();
            Review::query()->delete();
            BookingGuest::query()->delete();

            // Messagerie et favoris, indépendants des réservations.
            Message::query()->delete();
            Conversation::query()->delete();
            Favorite::query()->delete();

            // Blocages de calendrier avant les réservations : les deux tables
            // s'y référencent, autant les vider dans un ordre qui ne dépend
            // d'aucune cascade.
            AvailabilityBlock::query()->delete();

            // Les réservations elles-mêmes : plus rien n'en dépend à ce stade.
            Booking::withTrashed()->forceDelete();

            // Contenu propre à chaque villa.
            ComplianceCheck::query()->delete();
            PricingRule::query()->delete();
            PropertyImage::query()->delete();

            // Les villas, puis les propriétaires qu'elles retenaient.
            Property::withTrashed()->forceDelete();
            PropertyOwner::withTrashed()->forceDelete();

            // Notifications : elles pointent vers des réservations qui
            // viennent de disparaître, leurs liens seraient morts.
            DB::table('notifications')->delete();

            // Comptes clients fictifs. L'administrateur n'est jamais concerné :
            // ce n'est pas un rôle « customer ».
            User::customers()->withTrashed()->forceDelete();
        });

        $this->purgeStorage();

        $this->info('Nettoyage terminé.');
        $this->table(['Table', 'Lignes restantes'], collect($this->currentCounts())->map(
            fn (int $n, string $label) => [$label, $n]
        )->values()->all());

        return self::SUCCESS;
    }

    /** @return array<string, int> */
    private function currentCounts(): array
    {
        return [
            'clients (customer)' => User::customers()->withTrashed()->count(),
            'propriétaires' => PropertyOwner::withTrashed()->count(),
            'villas' => Property::withTrashed()->count(),
            'photos de villa' => PropertyImage::count(),
            'réservations' => Booking::withTrashed()->count(),
            'paiements' => Payment::count(),
            'avis' => Review::count(),
            'favoris' => Favorite::count(),
            'conversations' => Conversation::count(),
            'blocages de calendrier' => AvailabilityBlock::count(),
            'tarifs de saison' => PricingRule::count(),
            'pièces de conformité' => ComplianceCheck::count(),
        ];
    }

    /**
     * Retire les fichiers physiques que la suppression des lignes rend
     * orphelins : les photos de démonstration sur le disque public, et tout
     * document que la démonstration aurait déposé sur le disque privé.
     */
    private function purgeStorage(): void
    {
        $properties = Storage::disk('properties');

        if ($properties->exists('demo')) {
            $properties->deleteDirectory('demo');
            $this->comment('Photos de démonstration supprimées du disque « properties ».');
        }

        $compliance = Storage::disk('compliance');

        foreach ($compliance->directories() as $directory) {
            $compliance->deleteDirectory($directory);
        }
    }
}
