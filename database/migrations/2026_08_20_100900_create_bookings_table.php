<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->string('reference', 24)->unique();      // PCV-2026-000042

            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->date('checkin_date');
            $table->date('checkout_date');                  // borne exclue : on repart le matin
            $table->unsignedSmallInteger('nights');
            $table->unsignedSmallInteger('guests_count')->default(1);

            $table->string('status', 20)->default(BookingStatus::Pending->value);

            // Décomposition du prix, figée à la réservation. En francs entiers.
            $table->bigInteger('nightly_subtotal')->default(0);
            $table->bigInteger('cleaning_fee')->default(0);
            $table->bigInteger('service_fee')->default(0);
            $table->bigInteger('extra_fees_total')->default(0);
            $table->bigInteger('discount_total')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('security_deposit')->default(0);
            $table->string('currency', 3)->default('XOF');

            // Détail nuit par nuit du calcul, conservé pour justifier un prix a posteriori.
            $table->jsonb('price_breakdown')->nullable();

            /*
             * Taux et montants de commission figés au moment de la confirmation.
             * Un changement de taux ne doit jamais réécrire l'historique.
             */
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->bigInteger('commission_amount')->nullable();
            $table->bigInteger('owner_payout_amount')->nullable();

            $table->text('guest_note')->nullable();
            $table->text('admin_note')->nullable();

            $table->timestamp('hold_expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'checkin_date']);
            $table->index(['property_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('hold_expires_at');
        });

        DB::statement("ALTER TABLE bookings
            ADD COLUMN period daterange
            GENERATED ALWAYS AS (daterange(checkin_date, checkout_date, '[)')) STORED");

        DB::statement('ALTER TABLE bookings ADD CONSTRAINT bookings_dates_ordered
            CHECK (checkout_date > checkin_date)');

        DB::statement('ALTER TABLE bookings ADD CONSTRAINT bookings_nights_match
            CHECK (nights = (checkout_date - checkin_date))');

        DB::statement('ALTER TABLE bookings ADD CONSTRAINT bookings_amounts_non_negative CHECK (
            nightly_subtotal >= 0 AND cleaning_fee >= 0 AND service_fee >= 0
            AND extra_fees_total >= 0 AND discount_total >= 0 AND total_amount >= 0
            AND security_deposit >= 0 AND guests_count > 0
        )');

        DB::statement('CREATE INDEX bookings_period_gist ON bookings USING gist (property_id, period)');
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
