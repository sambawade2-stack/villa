<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Booking;
use App\Notifications\Concerns\BuildsBookingMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelled extends Notification implements ShouldQueue
{
    use BuildsBookingMail, Queueable;

    public function __construct(
        public readonly Booking $booking,
        public readonly ?string $reason = null,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__('Réservation annulée :ref', ['ref' => $this->booking->reference]))
            ->greeting(__('Bonjour :name,', ['name' => $notifiable->first_name]))
            ->line(__('La réservation :ref pour :villa a été annulée.', [
                'ref' => $this->booking->reference,
                'villa' => $this->booking->property?->name,
            ]));

        if ($this->reason !== null) {
            $mail->line(__('Motif : :reason', ['reason' => $this->reason]));
        }

        return $mail
            ->line(__('Les dates sont de nouveau disponibles à la réservation.'))
            ->action(__('Voir mes réservations'), route('bookings.index'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            ...$this->bookingPayload($this->booking),
            'type' => 'booking.cancelled',
            'title' => __('Réservation annulée'),
            'message' => $this->reason ?? __('Les dates ont été libérées.'),
            'url' => $this->bookingUrl($this->booking),
            'icon' => 'circle-x',
        ];
    }
}
