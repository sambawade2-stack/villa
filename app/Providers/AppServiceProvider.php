<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * Sans dédié app\Providers\EventServiceProvider (structure Laravel 11),
         * cet écouteur n'est pas enregistré par défaut : il faut le brancher
         * explicitement pour que l'inscription déclenche réellement l'envoi du
         * courriel de vérification.
         */
        Event::listen(Registered::class, SendEmailVerificationNotification::class);
    }
}
