<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\Commands\HandleOauthCallbackController;
use App\Http\Controllers\Auth\Commands\LoginTrooperController;
use App\Http\Controllers\Auth\Commands\LogoutTrooperController;
use App\Http\Controllers\Auth\Commands\RegisterTrooperController;
use App\Http\Controllers\Auth\Pages\RedirectToOauthProviderController;
use App\Http\Controllers\Auth\Pages\ShowInactiveController;
use App\Http\Controllers\Auth\Pages\ShowLoginFormController;
use App\Http\Controllers\Auth\Pages\ShowRegistrationFormController;
use App\Http\Controllers\Auth\Pages\ShowSignUpController;
use App\Http\Controllers\Auth\Pages\ShowThankYouController;
use App\Http\Controllers\Auth\Pages\StartEmailSignUpController;
use Illuminate\Support\Facades\Route;

// AUTH
Route::name('auth.')
    ->group(function ()
    {
        //  LOGIN / LOGOUT
        Route::get('/login', ShowLoginFormController::class)->name('login');
        Route::post('/login', LoginTrooperController::class);
        Route::get('/logout', LogoutTrooperController::class)->name('logout');

        //  REGISTRATION
        Route::get('/signup', ShowSignUpController::class)->name('signup');
        Route::get('/signup-email', StartEmailSignUpController::class)->name('signup-email');
        Route::get('/thank-you', ShowThankYouController::class)->name('thank-you');
        Route::get('/inactive', ShowInactiveController::class)->name('inactive');
        Route::get('/register', ShowRegistrationFormController::class)->middleware('auth.registration')->name('register');
        Route::post('/register', RegisterTrooperController::class)->middleware('auth.registration');

        // OAUTH
        Route::get('/oauth/{provider}', RedirectToOauthProviderController::class)->where('provider', '[a-z0-9_-]+')->name('oauth-redirect');
        Route::get('/oauth/{provider}/callback', HandleOauthCallbackController::class)->where('provider', '[a-z0-9_-]+')->name('oauth-callback');
    });
