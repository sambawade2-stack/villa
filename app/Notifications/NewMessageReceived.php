<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewMessageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Message $message,
        public readonly bool $forAdmin = false,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $conversation = $this->message->conversation;

        $url = $this->forAdmin
            ? route('admin.messages.show', $conversation)
            : route('messages.show', $conversation);

        return (new MailMessage)
            ->subject(__('Nouveau message — :subject', ['subject' => $conversation->subject]))
            ->line(__('« :extrait »', ['extrait' => Str::limit($this->message->body, 160)]))
            ->action(__('Répondre'), $url);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $conversation = $this->message->conversation;

        return [
            'type' => 'message.received',
            'title' => __('Nouveau message'),
            'message' => Str::limit($this->message->body, 90),
            'conversation_id' => $conversation->id,
            'url' => $this->forAdmin
                ? route('admin.messages.show', $conversation)
                : route('messages.show', $conversation),
            'icon' => 'message-circle',
        ];
    }
}
