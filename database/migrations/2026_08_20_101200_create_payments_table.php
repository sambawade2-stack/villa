<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();

            $table->string('gateway', 32);          // manual | paydunya | paytech | stripe
            $table->string('status', 24)->default(PaymentStatus::Pending->value);

            $table->bigInteger('amount');
            $table->bigInteger('refunded_amount')->default(0);
            $table->string('currency', 3)->default('XOF');

            // Identifiant du paiement chez le prestataire.
            $table->string('provider_reference')->nullable();
            $table->text('checkout_url')->nullable();

            $table->jsonb('payload')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();

            $table->timestamps();

            $table->index(['booking_id', 'status']);
            $table->index('gateway');
        });

        // Une même référence prestataire ne peut désigner deux paiements.
        DB::statement('CREATE UNIQUE INDEX payments_provider_reference_unique
            ON payments (gateway, provider_reference) WHERE provider_reference IS NOT NULL');

        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_amounts_coherent CHECK (
            amount >= 0 AND refunded_amount >= 0 AND refunded_amount <= amount
        )');
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
