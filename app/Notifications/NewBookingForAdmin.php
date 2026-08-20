<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Booking;
use App\Notifications\Concerns\BuildsBookingMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Prévient l'administration qu'une demande attend un règlement.
 *
 * C'est la notification qui déclenche l'action humaine : sans elle, personne ne
 * saurait qu'il faut guetter un virement.
 */
class NewBookingForAdmin extends Notification implements ShouldQueue
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
            ->subject(__('Nouvelle demande :ref — :villa', [
                'ref' => $this->booking->reference,
                'villa' => $this->booking->property?->name,
            ]))
            ->line(__('Client : :name (:email)', [
                'name' => $this->booking->user?->full_name,
                'email' => $this->booking->user?->email,
            ]))
            ->line(__('Séjour : du :from au :to, :guests voyageurs.', [
                'from' => $this->booking->checkin_date->format('d/m/Y'),
                'to' => $this->booking->checkout_date->format('d/m/Y'),
                'guests' => $this->booking->guests_count,
            ]))
            ->line(__('Montant attendu : :total.', ['total' => $this->booking->total_amount->format()]))
            ->action(__('Ouvrir la réservation'), $this->adminBookingUrl($this->booking));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            ...$this->bookingPayload($this->booking),
            'type' => 'booking.new_for_admin',
            'title' => __('Nouvelle demande de réservation'),
            'message' => __(':villa — :total', [
                'villa' => $this->booking->property?->name,
                'total' => $this->booking->total_amount->format(),
            ]),
            'url' => $this->adminBookingUrl($this->booking),
            'icon' => 'calendar',
        ];
    }
}
