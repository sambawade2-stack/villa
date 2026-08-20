<?php

declare(strict_types=1);

use App\Enums\ConversationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Messagerie v1 : Client ↔ Admin uniquement.
     *
     * Les propriétaires n'ayant pas de compte, l'administrateur fait
     * l'intermédiaire. La structure accueillera un fil direct Client ↔ Propriétaire
     * en v2 sans migration destructrice.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();  // le client
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();

            $table->string('subject')->nullable();
            $table->string('status', 20)->default(ConversationStatus::Open->value);

            $table->timestamp('last_message_at')->nullable();
            $table->unsignedInteger('customer_unread_count')->default(0);
            $table->unsignedInteger('admin_unread_count')->default(0);

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
