<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Booking;
use App\Notifications\Concerns\BuildsBookingMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class BookingConfirmed extends Notification implements ShouldQueue
{
    use BuildsBookingMail, Queueable;

    public function __construct(public readonly Booking $booking) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Réservation confirmée :ref', ['ref' => $this->booking->reference]))
            ->greeting(__('Bonjour :name,', ['name' => $notifiable->first_name]))
            ->line(__('Nous avons bien reçu votre règlement de :total.', [
                'total' => $this->booking->total_amount->format(),
            ]))
            ->line(__('Votre séjour à :villa est confirmé.', ['villa' => $this->booking->property?->name]))
            ->line(__('Arrivée le :from à partir de :time.', [
                'from' => $this->booking->checkin_date->translatedFormat('l d F Y'),
                'time' => Carbon::parse($this->booking->property?->checkin_time)->format('H:i'),
            ]))
            ->line(__('Départ le :to avant :time.', [
                'to' => $this->booking->checkout_date->translatedFormat('l d F Y'),
                'time' => Carbon::parse($this->booking->property?->checkout_time)->format('H:i'),
            ]))
            // L'adresse exacte n'est jamais dans le courriel : elle est
            // transmise dans le fil de messages, après confirmation.
            ->line(__('L\'adresse exacte et les consignes d\'arrivée vous parviennent par message.'))
            ->action(__('Voir ma réservation'), $this->bookingUrl($this->booking));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            ...$this->bookingPayload($this->booking),
            'type' => 'booking.confirmed',
            'title' => __('Réservation confirmée'),
            'message' => __('Votre séjour à :villa est confirmé.', ['villa' => $this->booking->property?->name]),
            'url' => $this->bookingUrl($this->booking),
            'icon' => 'circle-check',
        ];
    }
}
