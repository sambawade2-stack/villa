<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Séquence des références de réservation.
     *
     * Un compteur applicatif (« max(id) + 1 ») produirait des doublons sous
     * concurrence : deux requêtes simultanées liraient la même valeur. Une
     * séquence PostgreSQL est atomique par construction, et ne recule pas même
     * si la transaction qui l'a consommée est annulée — un numéro sauté vaut
     * mieux qu'un numéro en double.
     */
    public function up(): void
    {
        DB::statement('CREATE SEQUENCE IF NOT EXISTS booking_reference_seq START WITH 1 INCREMENT BY 1');
    }

    public function down(): void
    {
        DB::statement('DROP SEQUENCE IF EXISTS booking_reference_seq');
    }
};
