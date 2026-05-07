<?php

declare(strict_types=1);

use App\Enums\OauthProvider;
use App\Http\Controllers\Account\CostumesController;
use App\Http\Controllers\Account\CostumesDeleteHtmxController;
use App\Http\Controllers\Account\CostumesSubmitHtmxController;
use App\Http\Controllers\Account\NoticesController;
use App\Http\Controllers\Account\NoticesSubmitHtmxController;
use App\Http\Controllers\Account\NotificationsController;
use App\Http\Controllers\Account\NotificationsSubmitController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\PushNotificationClearController;
use App\Http\Controllers\Account\PushNotificationInboxController;
use App\Http\Controllers\Account\PushNotificationReadController;
use App\Http\Controllers\Account\ProfileSubmitController;
use App\Http\Controllers\Account\SetupController;
use App\Http\Controllers\Account\SetupSubmitController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\OauthLogin;

//  ACCOUNT
Route::prefix('account')
    ->name('account.')
    ->middleware('auth')
    ->group(function ()
    {
        Route::get('/profile', ProfileController::class)->name('profile');
        Route::post('/profile', ProfileSubmitController::class);
        Route::get('/notifications', NotificationsController::class)->name('notifications');
        Route::post('/notifications', NotificationsSubmitController::class);
        Route::get('/notices', NoticesController::class)->name('notices');
        Route::post('/notices-htmx/{notice}', NoticesSubmitHtmxController::class)->name('notices-htmx');
        Route::get('/costumes', CostumesController::class)->name('costumes');
        Route::post('/costumes-htmx', CostumesSubmitHtmxController::class)->name('costumes-htmx');
        Route::delete('/costumes-htmx', CostumesDeleteHtmxController::class);

        //  needed a post name to get the middleware to work properly
        Route::get('/push-notifications', PushNotificationInboxController::class)->name('push-notifications');
        Route::post('/push-notifications/{notification}/read', PushNotificationReadController::class)->name('push-notifications.read');
        Route::delete('/push-notifications', PushNotificationClearController::class)->name('push-notifications.clear');

        Route::get('/setup', SetupController::class)->name('setup');
        Route::post('/setup', SetupSubmitController::class)->name('setup-submit');

        // XenForo linking required page
        Route::get('/xenforo/required', function ()
        {
            $user = Auth::user();

            return view('pages.account.xenforo-required', [
                'user' => $user,
            ]);
        })->name('xenforo.required');

        // Optional: show current XenForo link status
        Route::get('/xenforo', function ()
        {
            $user = Auth::user();

            $xenforo_login = OauthLogin::where(OauthLogin::TROOPER_ID, $user->id)
                ->where(OauthLogin::PROVIDER, OauthProvider::XENFORO)
                ->first();

            return view('pages.account.xenforo', [
                'user' => $user,
                'xenforo_login' => $xenforo_login,
            ]);
        })->name('xenforo.index');
    });
