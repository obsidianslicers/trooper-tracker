<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\InactiveController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LoginSubmitController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\OauthCallbackController;
use App\Http\Controllers\Auth\OauthRedirectController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\RegisterHtmxController;
use App\Http\Controllers\Auth\RegisterSubmitController;
use App\Http\Controllers\Auth\SignUpController;
use App\Http\Controllers\Auth\SignUpEmailController;
use App\Http\Controllers\Auth\ThankYouController;
use Illuminate\Support\Facades\Route;

// AUTH
Route::name('auth.')
    ->group(function ()
    {
        $providers = ['google', 'xenforo'];

        //  LOGIN / LOGOUT
        Route::get('/login', LoginController::class)->name('login');
        Route::post('/login', LoginSubmitController::class);
        Route::get('/logout', LogoutController::class)->name('logout');

        //  REGISTRATION
        Route::get('/signup', SignUpController::class)->name('signup');
        Route::get('/signup-email', SignUpEmailController::class)->name('signup-email');
        Route::get('/thank-you', ThankYouController::class)->name('thank-you');
        Route::get('/inactive', InactiveController::class)->name('inactive');
        Route::get('/register', RegisterController::class)->middleware('auth.registration')->name('register');
        Route::post('/register', RegisterSubmitController::class)->middleware('auth.registration');
        Route::post('/register-htmx/{organization}', RegisterHtmxController::class)->middleware('auth.registration')->name('register-htmx');

        // OAUTH
        Route::get('/oauth/{provider}', OauthRedirectController::class)->whereIn('provider', $providers)->name('oauth-redirect');
        Route::get('/oauth/{provider}/callback', OauthCallbackController::class)->whereIn('provider', $providers)->name('oauth-callback');
    });