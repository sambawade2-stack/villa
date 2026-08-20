<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Models\Booking;

it('autorise uniquement les transitions prévues', function (BookingStatus $from, BookingStatus $to, bool $allowed) {
    expect($from->canTransitionTo($to))->toBe($allowed);
})->with([
    'en attente → confirmée' => [BookingStatus::Pending, BookingStatus::Confirmed, true],
    'en attente → annulée' => [BookingStatus::Pending, BookingStatus::Cancelled, true],
    'en attente → terminée' => [BookingStatus::Pending, BookingStatus::Completed, false],
    'confirmée → terminée' => [BookingStatus::Confirmed, BookingStatus::Completed, true],
    'confirmée → annulée' => [BookingStatus::Confirmed, BookingStatus::Cancelled, true],
    'confirmée → en attente' => [BookingStatus::Confirmed, BookingStatus::Pending, false],
    'annulée → remboursée' => [BookingStatus::Cancelled, BookingStatus::Refunded, true],
    'annulée → confirmée' => [BookingStatus::Cancelled, BookingStatus::Confirmed, false],
    'terminée → quoi que ce soit' => [BookingStatus::Completed, BookingStatus::Cancelled, false],
]);

it('sait quels statuts immobilisent des dates', function () {
    expect(BookingStatus::Pending->holdsDates())->toBeTrue()
        ->and(BookingStatus::Confirmed->holdsDates())->toBeTrue()
        ->and(BookingStatus::Cancelled->holdsDates())->toBeFalse()
        ->and(BookingStatus::Completed->holdsDates())->toBeFalse();
});

it('identifie les statuts terminaux', function () {
    expect(BookingStatus::Completed->isFinal())->toBeTrue()
        ->and(BookingStatus::Refunded->isFinal())->toBeTrue()
        ->and(BookingStatus::Pending->isFinal())->toBeFalse();
});

it('repère une tenue de dates expirée', function () {
    $fresh = Booking::factory()->create(['hold_expires_at' => now()->addMinutes(20)]);
    $stale = Booking::factory()->create(['hold_expires_at' => now()->subMinute()]);
    $confirmed = Booking::factory()->confirmed()->create(['hold_expires_at' => now()->subMinute()]);

    expect($fresh->holdHasExpired())->toBeFalse()
        ->and($stale->holdHasExpired())->toBeTrue()
        // Une réservation confirmée ne se périme pas, quelle que soit la date de tenue.
        ->and($confirmed->holdHasExpired())->toBeFalse()
        ->and(Booking::query()->expiredHolds()->count())->toBe(1);
});

it('impose une référence unique', function () {
    $booking = Booking::factory()->create();

    expect(sqlStateOf(fn () => Booking::factory()->create(['reference' => $booking->reference])))
        ->toBe(UNIQUE_VIOLATION);
});
