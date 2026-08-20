<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Gateways;

use App\Services\WhatsApp\Contracts\WhatsAppGateway;
use App\Services\WhatsApp\DTO\InboundMessage;
use App\Services\WhatsApp\DTO\OutboundMessage;
use App\Services\WhatsApp\DTO\SendResult;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * WhatsApp Cloud API (Meta).
 *
 * NON VÉRIFIÉE EN CONDITIONS RÉELLES : l'implémentation suit la documentation
 * de l'API Graph, mais elle n'a pas pu être éprouvée faute d'un compte
 * WhatsApp Business et d'un jeton. Elle reste inactive tant que
 * WHATSAPP_DRIVER n'est pas passé à « cloud ».
 *
 * Contrainte métier à connaître : hors d'une fenêtre de 24 heures après le
 * dernier message du client, Meta n'accepte que des modèles pré-approuvés.
 * Un message libre envoyé au-delà sera refusé par l'API — d'où la journalisation
 * systématique des échecs plutôt qu'une exception silencieuse.
 */
final class CloudApiGateway implements WhatsAppGateway
{
    public function name(): string
    {
        return 'cloud';
    }

    public function isConfigured(): bool
    {
        return filled(config('whatsapp.cloud.token'))
            && filled(config('whatsapp.cloud.phone_number_id'));
    }

    public function send(OutboundMessage $message): SendResult
    {
        if (! $this->isConfigured()) {
            return SendResult::failed('Identifiants WhatsApp Cloud absents.');
        }

        $endpoint = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            config('whatsapp.cloud.api_version'),
            config('whatsapp.cloud.phone_number_id'),
        );

        try {
            $response = Http::withToken(config('whatsapp.cloud.token'))
                ->timeout((int) config('whatsapp.cloud.timeout', 15))
                ->asJson()
                ->post($endpoint, [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $message->to,
                    'type' => 'text',
                    'text' => ['preview_url' => false, 'body' => $message->body],
                ]);
        } catch (Throwable $e) {
            Log::warning('WhatsApp Cloud : appel impossible', ['error' => $e->getMessage()]);

            return SendResult::failed($e->getMessage());
        }

        if ($response->failed()) {
            $error = (string) Arr::get($response->json(), 'error.message', $response->status());
            Log::warning('WhatsApp Cloud : envoi refusé', ['error' => $error]);

            return SendResult::failed($error, $response->json() ?? []);
        }

        return SendResult::ok(
            Arr::get($response->json(), 'messages.0.id'),
            $response->json() ?? [],
        );
    }

    /**
     * Vérifie la signature de la notification.
     *
     * Sans cette vérification, n'importe qui connaissant l'URL pourrait injecter
     * de faux messages dans les conversations. hash_equals évite en outre de
     * fuiter de l'information par le temps de comparaison.
     */
    public function verifyWebhook(Request $request): bool
    {
        $secret = config('whatsapp.cloud.app_secret');

        if (blank($secret)) {
            return false;
        }

        $signature = (string) $request->header('X-Hub-Signature-256', '');

        if (! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    public function parseWebhook(Request $request): array
    {
        $messages = [];

        foreach ((array) $request->input('entry', []) as $entry) {
            foreach ((array) Arr::get($entry, 'changes', []) as $change) {
                $value = Arr::get($change, 'value', []);
                /** @var array<int, array<string, mixed>> $contacts */
                $contacts = (array) Arr::get($value, 'contacts', []);

                $profiles = [];
                foreach ($contacts as $contact) {
                    $profiles[(string) Arr::get($contact, 'wa_id')] = $contact;
                }

                foreach ((array) Arr::get($value, 'messages', []) as $message) {
                    // Seuls les messages texte sont traités : une pièce jointe
                    // WhatsApp exige un second appel pour récupérer le média,
                    // ce qui viendra avec la gestion des documents entrants.
                    if (Arr::get($message, 'type') !== 'text') {
                        continue;
                    }

                    $from = (string) Arr::get($message, 'from');

                    $messages[] = new InboundMessage(
                        from: $from,
                        body: (string) Arr::get($message, 'text.body', ''),
                        externalId: (string) Arr::get($message, 'id'),
                        profileName: Arr::get($profiles[$from] ?? [], 'profile.name'),
                        sentAt: ($timestamp = Arr::get($message, 'timestamp'))
                            ? Carbon::createFromTimestamp((int) $timestamp)
                            : null,
                    );
                }
            }
        }

        return $messages;
    }
}
