<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Enums\ConversationStatus;
use App\Enums\MessageChannel;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Property;
use App\Models\User;
use App\Notifications\NewMessageReceived;
use App\Services\Notifications\Notifier;
use App\Services\WhatsApp\DTO\InboundMessage;
use App\Services\WhatsApp\DTO\OutboundMessage;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Support\Facades\DB;

/**
 * Messagerie Client ↔ Admin.
 *
 * En v1 le propriétaire n'a pas de compte : l'administrateur fait
 * l'intermédiaire. Un fil peut recevoir des messages de deux canaux, la
 * messagerie du site et WhatsApp, sans que le client ait à choisir.
 */
class MessagingService
{
    public function __construct(
        private readonly WhatsAppManager $whatsapp,
        private readonly Notifier $notifier,
    ) {}

    /** Ouvre le fil du client, ou reprend celui déjà ouvert sur le même sujet. */
    public function openConversation(
        User $customer,
        string $subject,
        ?Property $property = null,
        ?Booking $booking = null,
    ): Conversation {
        $existing = $customer->conversations()
            ->open()
            ->when($property, fn ($q) => $q->where('property_id', $property->id))
            ->when($booking, fn ($q) => $q->where('booking_id', $booking->id))
            ->when(! $property && ! $booking, fn ($q) => $q->whereNull('property_id')->whereNull('booking_id'))
            ->latest('last_message_at')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return Conversation::create([
            'user_id' => $customer->id,
            'property_id' => $property?->id,
            'booking_id' => $booking?->id,
            'subject' => $subject,
            'status' => ConversationStatus::Open,
            'whatsapp_number' => preg_replace('/\D+/', '', (string) $customer->whatsapp) ?: null,
        ]);
    }

    /**
     * Publie un message dans un fil.
     *
     * `$fromAdmin` détermine quel compteur de non-lus s'incrémente : celui du
     * client ou celui de l'administration.
     */
    public function post(
        Conversation $conversation,
        ?User $sender,
        string $body,
        MessageChannel $channel = MessageChannel::InApp,
        bool $fromAdmin = false,
        ?string $externalId = null,
    ): Message {
        $message = DB::transaction(function () use ($conversation, $sender, $body, $channel, $fromAdmin, $externalId) {
            $message = $conversation->messages()->create([
                'sender_id' => $sender?->id,
                'body' => $body,
                'channel' => $channel,
                'external_id' => $externalId,
            ]);

            $conversation->forceFill([
                'last_message_at' => now(),
                'status' => ConversationStatus::Open,
            ])->saveQuietly();

            $conversation->increment($fromAdmin ? 'customer_unread_count' : 'admin_unread_count');

            return $message;
        });

        // Le destinataire est l'autre partie : l'admin si le client écrit,
        // le client si l'admin répond.
        if ($fromAdmin) {
            $this->notifier->to($conversation->user, new NewMessageReceived($message));
        } else {
            $this->notifier->toAdmins(new NewMessageReceived($message, forAdmin: true));
        }

        return $message;
    }

    /**
     * Réponse de l'administration.
     *
     * Le message est toujours enregistré dans le fil. S'il est destiné à
     * WhatsApp, l'envoi est tenté ensuite : un échec de la passerelle marque le
     * message en erreur mais ne le fait pas disparaître de la conversation.
     */
    public function replyAsAdmin(
        Conversation $conversation,
        User $admin,
        string $body,
        MessageChannel $channel = MessageChannel::InApp,
    ): Message {
        $message = $this->post($conversation, $admin, $body, $channel, fromAdmin: true);

        if ($channel === MessageChannel::WhatsApp) {
            $this->deliverOverWhatsApp($conversation, $message);
        }

        return $message;
    }

    private function deliverOverWhatsApp(Conversation $conversation, Message $message): void
    {
        $number = $conversation->whatsapp_number;

        if (blank($number)) {
            $message->forceFill([
                'failed_at' => now(),
                'failure_reason' => __('Aucun numéro WhatsApp connu pour ce client.'),
            ])->saveQuietly();

            return;
        }

        $result = $this->whatsapp->gateway()->send(new OutboundMessage(
            to: $number,
            body: $message->body,
            messageId: $message->id,
        ));

        $message->forceFill($result->accepted
            ? ['delivered_at' => now(), 'external_id' => $result->externalId]
            : ['failed_at' => now(), 'failure_reason' => $result->error]
        )->saveQuietly();
    }

    /**
     * Enregistre un message WhatsApp entrant.
     *
     * Rattaché au client dont le numéro correspond ; à défaut, à un fil
     * identifié par le seul numéro, que l'administrateur pourra relier ensuite.
     * Retourne null si le message a déjà été traité — Meta réémet ses
     * notifications tant qu'elle n'a pas reçu de 200.
     */
    public function ingestWhatsApp(InboundMessage $inbound): ?Message
    {
        $alreadySeen = Message::query()
            ->where('channel', MessageChannel::WhatsApp)
            ->where('external_id', $inbound->externalId)
            ->exists();

        if ($alreadySeen) {
            return null;
        }

        $number = preg_replace('/\D+/', '', $inbound->from);

        $conversation = Conversation::query()
            ->where('whatsapp_number', $number)
            ->latest('last_message_at')
            ->first();

        if ($conversation === null) {
            $customer = User::query()
                ->customers()
                ->where(fn ($q) => $q->whereRaw("regexp_replace(coalesce(whatsapp, ''), '\\D', '', 'g') = ?", [$number])
                    ->orWhereRaw("regexp_replace(coalesce(phone, ''), '\\D', '', 'g') = ?", [$number]))
                ->first();

            $conversation = Conversation::create([
                'user_id' => $customer?->id,
                'subject' => __('Conversation WhatsApp — :name', [
                    'name' => $inbound->profileName ?: '+'.$number,
                ]),
                'status' => ConversationStatus::Open,
                'whatsapp_number' => $number,
            ]);
        }

        $message = $this->post(
            $conversation,
            $conversation->user,
            $inbound->body,
            MessageChannel::WhatsApp,
            fromAdmin: false,
            externalId: $inbound->externalId,
        );

        // Ouvre la fenêtre de 24 h pendant laquelle un message libre est permis.
        $conversation->forceFill(['last_inbound_at' => $inbound->sentAt ?? now()])->saveQuietly();

        return $message;
    }

    public function markReadForCustomer(Conversation $conversation): void
    {
        $conversation->messages()->whereNull('read_at')->update(['read_at' => now()]);
        $conversation->forceFill(['customer_unread_count' => 0])->saveQuietly();
    }

    public function markReadForAdmin(Conversation $conversation): void
    {
        $conversation->forceFill(['admin_unread_count' => 0])->saveQuietly();
    }
}
