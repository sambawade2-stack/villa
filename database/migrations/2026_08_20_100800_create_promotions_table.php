<?php

declare(strict_types=1);

use App\Enums\PromotionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();

            $table->string('code', 40)->unique();
            $table->jsonb('label')->nullable();

            $table->string('type', 20)->default(PromotionType::Percentage->value);
            // Pourcentage (0–100) ou montant fixe en francs, selon `type`.
            $table->decimal('value', 12, 2);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('max_uses_per_user')->nullable();
            $table->unsignedInteger('uses_count')->default(0);

            $table->unsignedSmallInteger('min_nights')->nullable();
            $table->bigInteger('min_total')->nullable();

            // Portée : globale si les deux clés sont nulles.
            $table->foreignId('destination_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->cascadeOnDelete();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        DB::statement('ALTER TABLE promotions ADD CONSTRAINT promotions_value_positive
            CHECK (value >= 0)');

        DB::statement("ALTER TABLE promotions ADD CONSTRAINT promotions_percentage_bounded
            CHECK (type <> 'percentage' OR value <= 100)");

        DB::statement('ALTER TABLE promotions ADD CONSTRAINT promotions_window_ordered
            CHECK (starts_at IS NULL OR ends_at IS NULL OR ends_at > starts_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
