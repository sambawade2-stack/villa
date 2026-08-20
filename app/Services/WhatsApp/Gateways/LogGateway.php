<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Gateways;

use App\Services\WhatsApp\Contracts\WhatsAppGateway;
use App\Services\WhatsApp\DTO\OutboundMessage;
use App\Services\WhatsApp\DTO\SendResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Passerelle de développement : écrit le message dans les journaux.
 *
 * Elle n'envoie rien. C'est délibéré et affiché comme tel : tant qu'aucun
 * identifiant Meta n'est fourni, faire croire à un envoi serait mentir.
 */
final class LogGateway implements WhatsAppGateway
{
    public function name(): string
    {
        return 'log';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function send(OutboundMessage $message): SendResult
    {
        $reference = 'log_'.Str::lower(Str::random(20));

        Log::channel(config('logging.default'))->info('WhatsApp (journal, aucun envoi réel)', [
            'to' => $message->to,
            'body' => Str::limit($message->body, 300),
            'reference' => $reference,
        ]);

        return SendResult::ok($reference, ['driver' => 'log']);
    }

    public function verifyWebhook(Request $request): bool
    {
        // Aucun webhook en mode journal.
        return false;
    }

    public function parseWebhook(Request $request): array
    {
        return [];
    }
}
