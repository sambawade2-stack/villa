<?php

declare(strict_types=1);

use App\Enums\BlockReason;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table pivot du système : c'est elle qui arbitre la disponibilité.
     *
     * La contrainte d'exclusion posée en fin de migration rend le chevauchement
     * physiquement impossible. Une vérification applicative laisserait toujours
     * une fenêtre de concurrence entre le SELECT et l'INSERT ; une contrainte
     * PostgreSQL, non — quel que soit le nombre de processus PHP simultanés.
     */
    public function up(): void
    {
        Schema::create('availability_blocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')->constrained()->cascadeOnDelete();

            $table->date('starts_on');
            $table->date('ends_on');           // borne exclue : libre pour l'arrivant du jour

            $table->string('reason', 20)->default(BlockReason::Manual->value);

            // Renseigné quand le blocage naît d'une réservation ; libéré avec elle.
            $table->foreignId('booking_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['property_id', 'starts_on']);
            $table->index('booking_id');
        });

        DB::statement("ALTER TABLE availability_blocks
            ADD COLUMN period daterange
            GENERATED ALWAYS AS (daterange(starts_on, ends_on, '[)')) STORED");

        DB::statement('ALTER TABLE availability_blocks ADD CONSTRAINT availability_blocks_dates_ordered
            CHECK (ends_on > starts_on)');

        // btree_gist est requis pour mélanger l'égalité (bigint) et le chevauchement (daterange).
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        DB::statement('ALTER TABLE availability_blocks
            ADD CONSTRAINT availability_blocks_no_overlap
            EXCLUDE USING gist (property_id WITH =, period WITH &&)');
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_blocks');
    }
};
