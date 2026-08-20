<?php

declare(strict_types=1);

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_owner_id')->constrained()->restrictOnDelete();
            $table->foreignId('destination_id')->constrained()->restrictOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->jsonb('description')->nullable();       // {"fr": …, "en": …}
            $table->jsonb('short_description')->nullable();

            $table->string('type', 20)->default(PropertyType::Villa->value);
            $table->string('status', 20)->default(PropertyStatus::Draft->value);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_featured')->default(false);

            // Capacité
            $table->unsignedSmallInteger('capacity')->default(2);
            $table->unsignedSmallInteger('bedrooms')->default(1);
            $table->unsignedSmallInteger('beds')->default(1);
            $table->unsignedSmallInteger('bathrooms')->default(1);
            $table->unsignedSmallInteger('surface_sqm')->nullable();

            // Localisation
            $table->string('neighborhood')->nullable();
            $table->string('zone')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            /*
             * Coordonnées floutées (~500 m) servies au public.
             * Les coordonnées exactes et l'adresse ne quittent jamais l'administration
             * tant qu'une réservation n'est pas confirmée.
             */
            $table->decimal('approx_latitude', 10, 7)->nullable();
            $table->decimal('approx_longitude', 10, 7)->nullable();
            $table->text('internal_address')->nullable();
            $table->text('internal_notes')->nullable();

            // Tarifs de référence, en francs CFA entiers (le XOF n'a pas de sous-unité).
            $table->bigInteger('base_price')->default(0);
            $table->bigInteger('weekend_price')->nullable();
            $table->bigInteger('weekly_price')->nullable();
            $table->bigInteger('high_season_price')->nullable();
            $table->bigInteger('low_season_price')->nullable();
            $table->bigInteger('cleaning_fee')->default(0);
            $table->bigInteger('security_deposit')->default(0);
            $table->jsonb('extra_fees')->nullable();        // [{label, amount, per}]

            // Règles de séjour
            $table->unsignedSmallInteger('min_nights')->default(1);
            $table->unsignedSmallInteger('max_nights')->nullable();
            $table->time('checkin_time')->default('15:00');
            $table->time('checkout_time')->default('11:00');
            $table->boolean('pets_allowed')->default(false);
            $table->boolean('parties_allowed')->default(false);
            $table->boolean('smoking_allowed')->default(false);
            $table->jsonb('house_rules')->nullable();       // règles libres, traduites

            // Agrégats d'avis, entretenus par l'application (évite un COUNT à chaque affichage).
            $table->decimal('rating_avg', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);

            // SEO
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'destination_id']);
            $table->index(['status', 'published_at']);
            $table->index('base_price');
            $table->index('capacity');
            $table->index('bedrooms');
            $table->index('property_owner_id');
            $table->index(['status', 'is_featured']);
        });

        // Garde-fous au niveau du moteur : aucun montant ni capacité négatifs.
        DB::statement('ALTER TABLE properties ADD CONSTRAINT properties_prices_non_negative CHECK (
            base_price >= 0
            AND cleaning_fee >= 0
            AND security_deposit >= 0
            AND (weekend_price IS NULL OR weekend_price >= 0)
            AND (weekly_price IS NULL OR weekly_price >= 0)
            AND (high_season_price IS NULL OR high_season_price >= 0)
            AND (low_season_price IS NULL OR low_season_price >= 0)
        )');

        DB::statement('ALTER TABLE properties ADD CONSTRAINT properties_capacity_positive CHECK (
            capacity > 0 AND bedrooms > 0 AND bathrooms > 0 AND min_nights > 0
            AND (max_nights IS NULL OR max_nights >= min_nights)
        )');
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
