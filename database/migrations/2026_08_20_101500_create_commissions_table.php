<?php

declare(strict_types=1);

use App\Enums\CommissionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('property_owner_id')->constrained()->restrictOnDelete();

            $table->decimal('rate', 5, 2);
            $table->bigInteger('base_amount');
            $table->bigInteger('commission_amount');
            $table->bigInteger('owner_payout_amount');

            $table->string('status', 20)->default(CommissionStatus::Pending->value);
            $table->timestamp('settled_at')->nullable();
            $table->string('settlement_note')->nullable();

            $table->timestamps();

            $table->index(['property_owner_id', 'status']);
        });

        DB::statement('ALTER TABLE commissions ADD CONSTRAINT commissions_amounts_coherent CHECK (
            rate >= 0 AND rate <= 100
            AND base_amount >= 0 AND commission_amount >= 0 AND owner_payout_amount >= 0
            AND commission_amount + owner_payout_amount = base_amount
        )');
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
