<?php

declare(strict_types=1);

use App\Services\Booking\BookingService;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Tâches planifiées
|--------------------------------------------------------------------------
|
| Lancées par `php artisan schedule:work` en développement, par une entrée cron
| appelant `schedule:run` chaque minute en production.
|
*/

/*
 * Libère les tenues de dates non payées.
 *
 * Sans ce passage, un panier abandonné gèlerait un calendrier indéfiniment :
 * la villa resterait invendable sur des dates que personne n'occupe.
 */
Schedule::call(function (BookingService $bookings) {
    $released = $bookings->expireStaleHolds();

    if ($released > 0) {
        logger()->info("Réservations expirées libérées : {$released}");
    }
})->everyFiveMinutes()->name('bookings:expire-holds')->withoutOverlapping();

/*
 * Clôt les séjours terminés — ce qui ouvre le droit de déposer un avis.
 */
Schedule::call(function (BookingService $bookings) {
    $completed = $bookings->completeFinishedStays();

    if ($completed > 0) {
        logger()->info("Séjours clos : {$completed}");
    }
})->dailyAt('05:00')->name('bookings:complete-stays')->withoutOverlapping();
