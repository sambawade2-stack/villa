<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Réglages modifiables par l'administrateur sans redéploiement :
     * taux de commission, frais de service, délai d'expiration des réservations,
     * politique d'annulation, coordonnées de contact.
     *
     * Lus via un cache Redis, invalidé à l'écriture.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            $table->string('key')->unique();
            $table->jsonb('value')->nullable();
            $table->string('group', 40)->default('general');
            $table->string('description')->nullable();

            $table->timestamps();

            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
