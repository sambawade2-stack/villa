<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Messaging\MessagingService;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Réception des messages WhatsApp.
 *
 * Route publique par nécessité — c'est Meta qui appelle — donc protégée par la
 * signature HMAC de la charge utile, jamais par une simple obscurité d'URL.
 */
class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private readonly WhatsAppManager $whatsapp,
        private readonly MessagingService $messaging,
    ) {}

    /**
     * Vérification de l'URL par Meta, à l'enregistrement du webhook.
     *
     * Meta appelle en GET avec un jeton ; on ne renvoie le défi que s'il
     * correspond à celui que nous avons configuré.
     */
    public function verify(Request $request): Response
    {
        $expected = config('whatsapp.cloud.verify_token');

        if (blank($expected)
            || $request->query('hub_mode') !== 'subscribe'
            || ! hash_equals((string) $expected, (string) $request->query('hub_verify_token'))) {
            return response('', 403);
        }

        return response((string) $request->query('hub_challenge'), 200)
            ->header('Content-Type', 'text/plain');
    }

    public function handle(Request $request): Response
    {
        $gateway = $this->whatsapp->gateway();

        if (! $gateway->verifyWebhook($request)) {
            Log::warning('WhatsApp : notification de signature invalide rejetée', [
                'ip' => $request->ip(),
            ]);

            return response('', 403);
        }

        foreach ($gateway->parseWebhook($request) as $inbound) {
            $this->messaging->ingestWhatsApp($inbound);
        }

        /*
         * 200 systématique après traitement : Meta réémet sa notification tant
         * qu'elle n'a pas de 200, et l'idempotence est assurée en amont par
         * l'index unique sur (channel, external_id).
         */
        return response('', 200);
    }
}
