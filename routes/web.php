<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ComplianceController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PropertyController as AdminPropertyController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Customer\FavoriteController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\DestinationController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PropertyController;
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

        Route::prefix('villas/{property}/conformite')->name('villas.compliance.')->group(function () {
            Route::get('/', [ComplianceController::class, 'show'])->name('show');
            Route::post('/{check}/document', [ComplianceController::class, 'upload'])->name('upload');
            Route::patch('/{check}', [ComplianceController::class, 'updateStatus'])->name('status');
            Route::get('/{check}/document', [ComplianceController::class, 'download'])->name('download');
            Route::delete('/{check}/document', [ComplianceController::class, 'destroyDocument'])->name('document.destroy');
        });
    });
