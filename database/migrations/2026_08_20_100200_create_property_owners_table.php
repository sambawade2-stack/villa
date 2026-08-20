<?php

declare(strict_types=1);

use App\Enums\OwnerStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_owners', function (Blueprint $table) {
            $table->id();

            /*
             * Charnière vers la v2.
             *
             * En v1 les propriétaires n'ont pas de compte : cette colonne reste nulle.
             * Le jour où le portail propriétaire arrive, on crée le User et on
             * renseigne la clé — sans toucher aux villas, réservations ni paiements.
             */
            $table->foreignId('user_id')->nullable()->unique()
                ->constrained()->nullOnDelete();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone', 32);
            $table->string('whatsapp', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('city')->nullable();

            // Données internes : jamais exposées publiquement.
            $table->text('internal_address')->nullable();
            $table->text('internal_notes')->nullable();

            $table->string('status', 20)->default(OwnerStatus::Active->value);

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_owners');
    }
};
