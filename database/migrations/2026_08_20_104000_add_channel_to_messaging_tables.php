<?php

declare(strict_types=1);

use App\Enums\MessageChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Numéro WhatsApp du client, quand la conversation passe par là.
            $table->string('whatsapp_number', 32)->nullable()->after('booking_id');

            /*
             * Meta n'autorise un message libre que dans les 24 h suivant le
             * dernier message du client. Au-delà, seuls des modèles approuvés
             * passent. On garde donc la trace du dernier message entrant.
             */
            $table->timestamp('last_inbound_at')->nullable()->after('last_message_at');

            $table->index('whatsapp_number');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->string('channel', 20)->default(MessageChannel::InApp->value)->after('sender_id');

            // sender_id est nul pour un message WhatsApp entrant : l'expéditeur
            // est un numéro, pas nécessairement un compte du site.
            $table->string('external_id')->nullable()->after('attachments');
            $table->timestamp('delivered_at')->nullable()->after('read_at');
            $table->timestamp('failed_at')->nullable()->after('delivered_at');
            $table->string('failure_reason')->nullable()->after('failed_at');

            $table->index('channel');
        });

        /*
         * Un message WhatsApp entrant peut venir d'un numéro qui n'a pas de
         * compte sur le site. Ni l'auteur du message, ni le titulaire du fil ne
         * peuvent donc être exigés : l'administrateur rattachera la conversation
         * à un client plus tard, ou jamais.
         */
        DB::statement('ALTER TABLE messages ALTER COLUMN sender_id DROP NOT NULL');
        DB::statement('ALTER TABLE conversations ALTER COLUMN user_id DROP NOT NULL');

        // Idempotence : Meta réémet ses notifications tant qu'elle n'a pas de 200.
        DB::statement('CREATE UNIQUE INDEX messages_external_id_unique
            ON messages (channel, external_id) WHERE external_id IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS messages_external_id_unique');
        DB::statement('ALTER TABLE conversations ALTER COLUMN user_id SET NOT NULL');
        DB::statement('ALTER TABLE messages ALTER COLUMN sender_id SET NOT NULL');

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['channel']);
            $table->dropColumn(['channel', 'external_id', 'delivered_at', 'failed_at', 'failure_reason']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['whatsapp_number']);
            $table->dropColumn(['whatsapp_number', 'last_inbound_at']);
        });
    }
};
