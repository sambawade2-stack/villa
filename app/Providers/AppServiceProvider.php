<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

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

        // Pont vers l'API HTTP de Brevo (voir config/mail.php, mailer "brevo") :
        // Laravel ne connaît pas nativement ce transport Symfony tiers.
        Mail::extend('brevo', fn (array $config) => (new BrevoTransportFactory)->create(Dsn::fromString($config['dsn'])));
    }
}
