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
        Schema::create('property_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')->constrained()->cascadeOnDelete();

            $table->string('path');
            $table->string('disk', 32)->default('properties');
            $table->jsonb('alt')->nullable();

            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_primary')->default(false);

            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedInteger('size')->nullable();     // octets

            // Chemins des dérivés : {"thumb": "…", "card": "…", "hero": "…", "full": "…"}
            $table->jsonb('conversions')->nullable();
            $table->timestamp('converted_at')->nullable();

            $table->timestamps();

            $table->index(['property_id', 'position']);
        });

        // Une seule image principale par villa — garanti par la base, pas par le code.
        DB::statement('CREATE UNIQUE INDEX property_images_one_primary
            ON property_images (property_id) WHERE is_primary');
    }

    public function down(): void
    {
        Schema::dropIfExists('property_images');
    }
};
