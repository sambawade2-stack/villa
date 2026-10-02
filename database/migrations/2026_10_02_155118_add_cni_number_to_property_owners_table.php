<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_owners', function (Blueprint $table) {
            // Numéro de carte d'identité : saisi depuis l'écran de conformité
            // de n'importe laquelle de ses villas, partagé puisqu'il s'agit
            // de la même personne, pas d'une pièce propre à chaque villa.
            $table->string('cni_number', 32)->nullable()->after('internal_address');
        });
    }

    public function down(): void
    {
        Schema::table('property_owners', function (Blueprint $table) {
            $table->dropColumn('cni_number');
        });
    }
};
