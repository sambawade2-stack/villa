<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')->constrained()->cascadeOnDelete();

            $table->string('label');
            $table->date('starts_on');
            $table->date('ends_on');           // borne exclue

            $table->bigInteger('price_per_night');
            $table->unsignedSmallInteger('min_nights')->nullable();

            // La règle de plus forte priorité l'emporte en cas de recouvrement :
            // ici le chevauchement est légitime (haute saison + Noël, par exemple).
            $table->unsignedSmallInteger('priority')->default(0);

            $table->timestamps();

            $table->index(['property_id', 'starts_on']);
        });

        DB::statement("ALTER TABLE pricing_rules
            ADD COLUMN period daterange
            GENERATED ALWAYS AS (daterange(starts_on, ends_on, '[)')) STORED");

        DB::statement('ALTER TABLE pricing_rules ADD CONSTRAINT pricing_rules_dates_ordered
            CHECK (ends_on > starts_on)');

        DB::statement('ALTER TABLE pricing_rules ADD CONSTRAINT pricing_rules_price_positive
            CHECK (price_per_night >= 0)');

        DB::statement('CREATE INDEX pricing_rules_period_gist ON pricing_rules USING gist (property_id, period)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
