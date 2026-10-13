<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * commission_amount mêle désormais la commission sur les nuits et les
     * frais de ménage/service (voir BookingService::confirm) : ce nouveau
     * champ isole la seule part liée au taux, pour un suivi de revenu
     * au tableau de bord qui ne confonde pas les deux.
     */
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->bigInteger('rate_commission_amount')->default(0)->after('base_amount');
        });

        // Rétro-calcul pour les commissions déjà figées : rate % du
        // sous-total des nuits de leur réservation, telle quelle à l'époque.
        DB::statement(<<<'SQL'
            UPDATE commissions
            SET rate_commission_amount = ROUND(bookings.nightly_subtotal * commissions.rate / 100)
            FROM bookings
            WHERE bookings.id = commissions.booking_id
        SQL);
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropColumn('rate_commission_amount');
        });
    }
};
