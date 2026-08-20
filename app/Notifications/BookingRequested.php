<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Setting;
use App\Notifications\Concerns\BuildsBookingMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Le client vient de poser une demande : ses dates sont tenues, le règlement
 * est attendu. Le courriel rappelle le délai, sans quoi la tenue expirerait
 * sans que personne ne comprenne pourquoi.
 */
class BookingRequested extends Notification implements ShouldQueue
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
        $minutes = (int) Setting::get('booking.hold_minutes', 30);

        return (new MailMessage)
            ->subject(__('Votre demande de réservation :ref', ['ref' => $this->booking->reference]))
            ->greeting(__('Bonjour :name,', ['name' => $notifiable->first_name]))
            ->line(__('Nous avons bien reçu votre demande pour :villa, du :from au :to.', [
                'villa' => $this->booking->property?->name,
                'from' => $this->booking->checkin_date->translatedFormat('d F Y'),
                'to' => $this->booking->checkout_date->translatedFormat('d F Y'),
            ]))
            ->line(__('Montant à régler : :total.', ['total' => $this->booking->total_amount->format()]))
            ->line(__('Vos dates sont tenues :minutes minutes, le temps du règlement.', ['minutes' => $minutes]))
            ->action(__('Finaliser ma réservation'), $this->bookingUrl($this->booking))
            ->line(__('Une question ? Répondez à ce message ou écrivez-nous sur WhatsApp.'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            ...$this->bookingPayload($this->booking),
            'type' => 'booking.requested',
            'title' => __('Demande enregistrée'),
            'message' => __('Vos dates sont tenues le temps du règlement.'),
            'url' => $this->bookingUrl($this->booking),
            'icon' => 'calendar',
        ];
    }
}
