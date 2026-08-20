<?php

declare(strict_types=1);

use App\Enums\TransactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Journal de tout échange avec une passerelle : initialisation, webhook,
     * vérification, remboursement. Sert de piste d'audit et d'antidote au rejeu.
     */
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();

            // Dupliqué depuis payments pour porter la clé d'idempotence ci-dessous.
            $table->string('gateway', 32);
            $table->string('type', 24)->default(TransactionType::Webhook->value);
            $table->string('status', 24)->nullable();

            $table->bigInteger('amount')->nullable();

            $table->string('provider_reference')->nullable();
            /*
             * Identifiant de l'événement chez le prestataire.
             * L'index unique plus bas fait qu'un webhook rejoué — cas courant :
             * les passerelles réémettent tant qu'elles n'ont pas de 200 — ne peut
             * pas confirmer deux fois la même réservation.
             */
            $table->string('provider_event_id')->nullable();

            $table->jsonb('raw_payload')->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'type']);
        });

        DB::statement('CREATE UNIQUE INDEX payment_transactions_event_unique
            ON payment_transactions (gateway, provider_event_id) WHERE provider_event_id IS NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
