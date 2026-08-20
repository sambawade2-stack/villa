<?php

declare(strict_types=1);

use App\Enums\ReviewStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // Un avis par séjour : la contrainte d'unicité rend le doublon impossible.
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Les cinq critères, notés de 1 à 5.
            $table->unsignedSmallInteger('cleanliness');
            $table->unsignedSmallInteger('location');
            $table->unsignedSmallInteger('communication');
            $table->unsignedSmallInteger('amenities');
            $table->unsignedSmallInteger('value_for_money');
            $table->decimal('overall', 3, 2);       // moyenne des cinq, calculée à l'écriture

            $table->text('comment')->nullable();

            $table->string('status', 20)->default(ReviewStatus::Pending->value);
            $table->timestamp('published_at')->nullable();

            // Réponse publique de l'administration.
            $table->text('admin_reply')->nullable();
            $table->timestamp('replied_at')->nullable();

            $table->timestamps();

            $table->index(['property_id', 'status']);
            $table->index(['status', 'published_at']);
        });

        DB::statement('ALTER TABLE reviews ADD CONSTRAINT reviews_scores_bounded CHECK (
            cleanliness BETWEEN 1 AND 5
            AND location BETWEEN 1 AND 5
            AND communication BETWEEN 1 AND 5
            AND amenities BETWEEN 1 AND 5
            AND value_for_money BETWEEN 1 AND 5
            AND overall BETWEEN 1 AND 5
        )');
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
