<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\BookingActionController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\ComplianceController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MessageController as AdminMessageController;
use App\Http\Controllers\Admin\PropertyController as AdminPropertyController;
use App\Http\Controllers\Admin\PropertyOwnerController as AdminOwnerController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Customer\BookingController;
use App\Http\Controllers\Customer\FavoriteController;
use App\Http\Controllers\Customer\MessageController;
use App\Http\Controllers\Customer\NotificationController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\DestinationController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PropertyController;
use App\Http\Controllers\Webhooks\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketplace publique
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');

Route::get('/villas', [PropertyController::class, 'index'])->name('villas.index');
Route::get('/villas/{property}', [PropertyController::class, 'show'])->name('villas.show');

Route::get('/destinations', [DestinationController::class, 'index'])->name('destinations.index');
Route::get('/destinations/{destination}', [DestinationController::class, 'show'])->name('destinations.show');

Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/a-propos', [PageController::class, 'about'])->name('about');

Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

Route::get('/langue/{locale}', LocaleController::class)->name('locale.switch');

/*
|--------------------------------------------------------------------------
| Espace client
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/favoris', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favoris/{property}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    Route::post('/villas/{property}/reserver', [BookingController::class, 'store'])
        ->middleware('throttle:20,1')->name('bookings.store');

    Route::get('/reservations', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/reservations/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::get('/reservations/{booking}/paiement', [BookingController::class, 'checkout'])->name('bookings.checkout');
    Route::post('/reservations/{booking}/paiement', [BookingController::class, 'pay'])
        ->middleware('throttle:20,1')->name('bookings.pay');
    Route::post('/reservations/{booking}/annuler', [BookingController::class, 'cancel'])->name('bookings.cancel');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessageController::class, 'create'])->middleware('throttle:20,1')->name('messages.create');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}', [MessageController::class, 'store'])
        ->middleware('throttle:40,1')->name('messages.store');
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
});

Route::post('/deconnexion', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

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

        Route::get('/proprietaires', [AdminOwnerController::class, 'index'])->name('owners.index');
        Route::get('/proprietaires/nouveau', [AdminOwnerController::class, 'create'])->name('owners.create');
        Route::post('/proprietaires', [AdminOwnerController::class, 'store'])->name('owners.store');
        Route::get('/proprietaires/{owner}', [AdminOwnerController::class, 'show'])->name('owners.show');
        Route::get('/proprietaires/{owner}/modifier', [AdminOwnerController::class, 'edit'])->name('owners.edit');
        Route::put('/proprietaires/{owner}', [AdminOwnerController::class, 'update'])->name('owners.update');

        Route::get('/reservations', [AdminBookingController::class, 'index'])->name('bookings.index');
        Route::get('/reservations/{booking}', [AdminBookingController::class, 'show'])->name('bookings.show');
        Route::post('/reservations/{booking}/paiement', [BookingActionController::class, 'confirmPayment'])->name('bookings.confirm-payment');
        Route::post('/reservations/{booking}/annuler', [BookingActionController::class, 'cancel'])->name('bookings.cancel');

        Route::get('/clients', [AdminCustomerController::class, 'index'])->name('customers.index');

        Route::get('/avis', [AdminReviewController::class, 'index'])->name('reviews.index');
        Route::patch('/avis/{review}', [AdminReviewController::class, 'update'])->name('reviews.update');

        Route::get('/parametres', [AdminSettingController::class, 'edit'])->name('settings.edit');
        Route::put('/parametres', [AdminSettingController::class, 'update'])->name('settings.update');

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
