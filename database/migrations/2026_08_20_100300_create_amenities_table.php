<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();

            $table->jsonb('name');
            $table->string('slug')->unique();
            $table->string('icon', 64)->nullable();

            // Regroupe les équipements dans la fiche villa : extérieur, confort, services…
            $table->string('category', 40)->default('general');

            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);

            // Les équipements mis en avant dans les filtres de recherche.
            $table->boolean('is_filterable')->default(false);

            $table->timestamps();

            $table->index(['category', 'position']);
            $table->index('is_filterable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenities');
    }
};
