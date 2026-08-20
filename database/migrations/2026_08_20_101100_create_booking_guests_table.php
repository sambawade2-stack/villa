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
        Schema::create('booking_guests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();

            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();

            // Le voyageur principal, celui que l'on contacte.
            $table->boolean('is_lead')->default(false);
            $table->string('age_group', 20)->default('adult');   // adult | child | infant

            $table->timestamps();

            $table->index('booking_id');
        });

        DB::statement('CREATE UNIQUE INDEX booking_guests_one_lead
            ON booking_guests (booking_id) WHERE is_lead');
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_guests');
    }
};
