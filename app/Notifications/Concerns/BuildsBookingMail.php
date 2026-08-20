<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

use App\Models\Booking;

trait BuildsBookingMail
{
    /**
     * Données communes à tous les courriels de réservation.
     *
     * Ce que l'on stocke en base de notifications reste volontairement maigre :
     * de quoi afficher une ligne et un lien, jamais l'adresse de la villa ni
     * les coordonnées du propriétaire.
     *
     * @return array<string, mixed>
     */
    protected function bookingPayload(Booking $booking): array
    {
        return [
            'booking_id' => $booking->id,
            'reference' => $booking->reference,
            'property' => $booking->property?->name,
            'destination' => (string) $booking->property?->destination?->name,
            'checkin' => $booking->checkin_date->toDateString(),
            'checkout' => $booking->checkout_date->toDateString(),
            'nights' => $booking->nights,
            'total_formatted' => $booking->total_amount->format(),
        ];
    }

    protected function bookingUrl(Booking $booking): string
    {
        return route('bookings.show', $booking);
    }

    protected function adminBookingUrl(Booking $booking): string
    {
        return route('admin.bookings.show', $booking);
    }
}
