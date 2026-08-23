<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AvailabilityBlockController;
use App\Http\Controllers\Admin\BookingActionController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\ComplianceController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MessageController as AdminMessageController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\PricingRuleController;
use App\Http\Controllers\Admin\PropertyController as AdminPropertyController;
use App\Http\Controllers\Admin\PropertyImageController as AdminPropertyImageController;
use App\Http\Controllers\Admin\PropertyOwnerController as AdminOwnerController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Customer\BookingController;
use App\Http\Controllers\Customer\FavoriteController;
use App\Http\Controllers\Customer\MessageController;
use App\Http\Controllers\Customer\NotificationController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Owner\AvailabilityBlockController as OwnerAvailabilityBlockController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\DestinationController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PropertyController;
use App\Http\Controllers\Seo\SitemapController;
use App\Http\Controllers\Webhooks\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketplace publique
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');

Route::get('/villas', [PropertyController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('villas.index');

/*
 * Adresse canonique de la fiche villa : /villas/{destination}/{villa}.
 * Déclarée avant la forme à un segment, qui ne sert plus qu'à rediriger les
 * anciens liens — un contenu ne doit exister qu'à une seule adresse.
 */
Route::get('/villas/{destination}/{property}', [PropertyController::class, 'show'])->name('villas.show');
Route::get('/villas/{property}', [PropertyController::class, 'legacyShow'])->name('villas.legacy');

Route::get('/destinations', [DestinationController::class, 'index'])->name('destinations.index');
Route::get('/destinations/{destination}', [DestinationController::class, 'show'])->name('destinations.show');

Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/a-propos', [PageController::class, 'about'])->name('about');

Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/langue/{locale}', LocaleController::class)->name('locale.switch');

/*
|--------------------------------------------------------------------------
| Espace client
|--------------------------------------------------------------------------
*/

/*
 * Espace voyageur, fermé aux administrateurs.
 *
 * Un administrateur gère les réservations des clients depuis /admin ; il n'en a
 * pas à son nom. Sans cette frontière, exploitation et usage se mélangeraient
 * dans le chiffre d'affaires comme dans les commissions.
 */
Route::middleware(['auth', 'customer'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::get('/favoris', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favoris/{property}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    /*
     * 'verified' seulement sur les deux gestes qui engagent réellement une
     * réservation ou un règlement : c'est l'adresse de ce compte qui reçoit
     * la confirmation et les instructions de paiement, elle doit donc être
     * prouvée avant que quoi que ce soit ne s'y appuie. Consulter, régler une
     * demande déjà en cours ou l'annuler restent ouverts sans cette barrière.
     */
    Route::post('/villas/{property}/reserver', [BookingController::class, 'store'])
        ->middleware(['verified', 'throttle:20,1'])->name('bookings.store');

    Route::get('/reservations', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/reservations/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::get('/reservations/{booking}/paiement', [BookingController::class, 'checkout'])->name('bookings.checkout');
    Route::post('/reservations/{booking}/paiement', [BookingController::class, 'pay'])
        ->middleware(['verified', 'throttle:20,1'])->name('bookings.pay');
    Route::post('/reservations/{booking}/annuler', [BookingController::class, 'cancel'])->name('bookings.cancel');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessageController::class, 'create'])->middleware('throttle:20,1')->name('messages.create');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}', [MessageController::class, 'store'])
        ->middleware('throttle:40,1')->name('messages.store');
});

/*
|--------------------------------------------------------------------------
| Espace propriétaire
|--------------------------------------------------------------------------
|
| Pour la transparence : chaque propriétaire suit l'état de sa villa et
| bloque ses propres dates, sans dépendre entièrement de l'administrateur.
| Lecture seule sur tout ce qui touche aux réservations et aux paiements.
|
*/
Route::middleware(['auth', 'owner'])->prefix('proprietaire')->name('owner.')->group(function () {
    Route::get('/', [OwnerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/villas/{property}', [OwnerDashboardController::class, 'show'])->name('villas.show');

    Route::post('/villas/{property}/blocages', [OwnerAvailabilityBlockController::class, 'store'])
        ->middleware('throttle:20,1')->name('villas.blocks.store');
    Route::delete('/villas/{property}/blocages/{block}', [OwnerAvailabilityBlockController::class, 'destroy'])
        ->name('villas.blocks.destroy');
});

/*
|--------------------------------------------------------------------------
| Webhooks
|--------------------------------------------------------------------------
|
| Appelés par des services tiers : hors session, hors CSRF, authentifiés par
| signature. Voir bootstrap/app.php pour l'exclusion CSRF.
|
*/

Route::prefix('webhooks/whatsapp')->name('webhooks.whatsapp.')->group(function () {
    Route::get('/', [WhatsAppWebhookController::class, 'verify'])->name('verify');
    Route::post('/', [WhatsAppWebhookController::class, 'handle'])->name('handle');
});

/*
|--------------------------------------------------------------------------
| Authentification
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store'])->middleware('throttle:20,1');

    Route::get('/inscription', [RegisterController::class, 'create'])->name('register');
    Route::post('/inscription', [RegisterController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/mot-de-passe/oublie', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/mot-de-passe/oublie', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:5,1')->name('password.email');

    /*
     * Le nom 'password.reset' est imposé : Illuminate\Auth\Notifications\
     * ResetPassword construit le lien du courriel via route('password.reset', …)
     * sans callback personnalisé — le renommer casserait le lien envoyé.
     */
    Route::get('/mot-de-passe/reinitialiser/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/mot-de-passe/reinitialiser', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:5,1')->name('password.update');
});

Route::post('/deconnexion', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Vérification de l'e-mail
|--------------------------------------------------------------------------
|
| 'verification.notice' et 'verification.verify' sont des noms imposés par le
| framework : EnsureEmailIsVerified et le lien signé du courriel les
| référencent directement, sans point de personnalisation.
|
*/

Route::middleware('auth')->group(function () {
    Route::get('/email/verifier', [EmailVerificationController::class, 'notice'])->name('verification.notice');

    Route::get('/email/verifier/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Route::post('/email/renvoyer', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
|
| Réservée à l'administrateur. Le middleware renvoie 404 et non 403 : rien ne
| justifie de confirmer l'existence de ces écrans à un visiteur.
|
*/

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::get('/villas', [AdminPropertyController::class, 'index'])->name('villas.index');
        Route::get('/villas/nouvelle', [AdminPropertyController::class, 'create'])->name('villas.create');
        Route::post('/villas', [AdminPropertyController::class, 'store'])->name('villas.store');
        Route::get('/villas/{property}/modifier', [AdminPropertyController::class, 'edit'])->name('villas.edit');
        Route::put('/villas/{property}', [AdminPropertyController::class, 'update'])->name('villas.update');
        Route::post('/villas/{property}/publier', [AdminPropertyController::class, 'publish'])->name('villas.publish');
        Route::post('/villas/{property}/retirer', [AdminPropertyController::class, 'unpublish'])->name('villas.unpublish');
        Route::delete('/villas/{property}', [AdminPropertyController::class, 'destroy'])->name('villas.destroy');

        Route::post('/villas/{property}/photos', [AdminPropertyImageController::class, 'store'])->name('villas.photos.store');
        Route::post('/villas/{property}/photos/{image}/couverture', [AdminPropertyImageController::class, 'makePrimary'])->name('villas.photos.primary');
        Route::post('/villas/{property}/photos/ordre', [AdminPropertyImageController::class, 'reorder'])->name('villas.photos.reorder');
        Route::delete('/villas/{property}/photos/{image}', [AdminPropertyImageController::class, 'destroy'])->name('villas.photos.destroy');

        Route::post('/villas/{property}/tarifs', [PricingRuleController::class, 'store'])->name('villas.pricing.store');
        Route::put('/villas/{property}/tarifs/{rule}', [PricingRuleController::class, 'update'])->name('villas.pricing.update');
        Route::delete('/villas/{property}/tarifs/{rule}', [PricingRuleController::class, 'destroy'])->name('villas.pricing.destroy');

        Route::post('/villas/{property}/blocages', [AvailabilityBlockController::class, 'store'])->name('villas.blocks.store');
        Route::delete('/villas/{property}/blocages/{block}', [AvailabilityBlockController::class, 'destroy'])->name('villas.blocks.destroy');

        Route::get('/proprietaires', [AdminOwnerController::class, 'index'])->name('owners.index');
        Route::get('/proprietaires/nouveau', [AdminOwnerController::class, 'create'])->name('owners.create');
        Route::post('/proprietaires', [AdminOwnerController::class, 'store'])->name('owners.store');
        Route::get('/proprietaires/{owner}', [AdminOwnerController::class, 'show'])->name('owners.show');
        Route::get('/proprietaires/{owner}/modifier', [AdminOwnerController::class, 'edit'])->name('owners.edit');
        Route::put('/proprietaires/{owner}', [AdminOwnerController::class, 'update'])->name('owners.update');
        Route::post('/proprietaires/{owner}/acces', [AdminOwnerController::class, 'grantAccess'])->name('owners.grant-access');

        Route::get('/reservations', [AdminBookingController::class, 'index'])->name('bookings.index');
        Route::get('/reservations/{booking}', [AdminBookingController::class, 'show'])->name('bookings.show');
        Route::post('/reservations/{booking}/paiement', [BookingActionController::class, 'confirmPayment'])->name('bookings.confirm-payment');
        Route::post('/reservations/{booking}/annuler', [BookingActionController::class, 'cancel'])->name('bookings.cancel');

        Route::get('/clients', [AdminCustomerController::class, 'index'])->name('customers.index');

        Route::get('/avis', [AdminReviewController::class, 'index'])->name('reviews.index');
        Route::patch('/avis/{review}', [AdminReviewController::class, 'update'])->name('reviews.update');

        Route::get('/parametres', [AdminSettingController::class, 'edit'])->name('settings.edit');
        Route::put('/parametres', [AdminSettingController::class, 'update'])->name('settings.update');

        Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{notification}', [AdminNotificationController::class, 'read'])->name('notifications.read');
        Route::post('/notifications', [AdminNotificationController::class, 'readAll'])->name('notifications.read-all');

        Route::get('/messages', [AdminMessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/{conversation}', [AdminMessageController::class, 'show'])->name('messages.show');
        Route::post('/messages/{conversation}', [AdminMessageController::class, 'reply'])->name('messages.reply');
        Route::patch('/messages/{conversation}/statut', [AdminMessageController::class, 'toggleStatus'])->name('messages.status');

        Route::prefix('villas/{property}/conformite')->name('villas.compliance.')->group(function () {
            Route::get('/', [ComplianceController::class, 'show'])->name('show');
            Route::post('/{check}/document', [ComplianceController::class, 'upload'])->name('upload');
            Route::patch('/{check}', [ComplianceController::class, 'updateStatus'])->name('status');
            Route::get('/{check}/document', [ComplianceController::class, 'download'])->name('download');
            Route::delete('/{check}/document', [ComplianceController::class, 'destroyDocument'])->name('document.destroy');
        });
    });
