<?php

declare(strict_types=1);

use App\Enums\OauthProvider;
use App\Http\Controllers\Account\Commands\AddCostumeController;
use App\Http\Controllers\Account\Commands\AddTrooperRequestController;
use App\Http\Controllers\Account\Commands\CancelDeletionController;
use App\Http\Controllers\Account\Commands\RemoveCostumeController;
use App\Http\Controllers\Account\Commands\RenewVisitorMembershipController;
use App\Http\Controllers\Account\Commands\UpdateNotificationFrequencyController;
use App\Http\Controllers\Account\Commands\UpdateNotificationPreferenceController;
use App\Http\Controllers\Account\Commands\UpdateOrganizationNotificationsController;
use App\Http\Controllers\Account\Commands\UpdateProfileController;
use App\Http\Controllers\Account\Commands\UpdatePushNotificationsController;
use App\Http\Controllers\Account\DeniedController;
use App\Http\Controllers\Account\DeniedResubmitController;
use App\Http\Controllers\Account\NoticesController;
use App\Http\Controllers\Account\NoticesSubmitHtmxController;
use App\Http\Controllers\Account\Pages\IndexController;
use App\Http\Controllers\Account\Pages\RenewVisitorController;
use App\Http\Controllers\Account\Pages\PendingMembershipController;
use App\Http\Controllers\Account\PushNotificationClearController;
use App\Http\Controllers\Account\PushNotificationInboxController;
use App\Http\Controllers\Account\PushNotificationReadController;
use App\Http\Controllers\Account\Commands\RequestDeletionController;
use App\Http\Controllers\Account\SetupController;
use App\Http\Controllers\Account\SetupSubmitController;
use App\Models\OauthLogin;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

//  ACCOUNT — denied/pending holding routes (auth only, no redirect middleware)
Route::prefix('account')
    ->name('account.')
    ->middleware('auth')
    ->group(function ()
    {
        Route::get('/denied', DeniedController::class)->name('denied');
        Route::post('/denied/resubmit', DeniedResubmitController::class)->name('denied.resubmit');
        Route::get('/pending', PendingMembershipController::class)->name('pending');
    });

//  ACCOUNT
Route::prefix('account')
    ->name('account.')
    ->middleware(['auth', 'redirect.denied', 'redirect.pending'])
    ->group(function ()
    {
        Route::get('/', IndexController::class)->name('index');
        Route::post('/update/profile', UpdateProfileController::class)->name('update-profile');
        Route::post('/update/notifications/frequency', UpdateNotificationFrequencyController::class)->name('update-notification-frequency');
        Route::post('/update/notifications/push', UpdatePushNotificationsController::class)->name('update-push-notifications');
        Route::post('/update/notifications/organization', UpdateOrganizationNotificationsController::class)->name('update-organization-notifications');
        Route::post('/update/notifications/preference', UpdateNotificationPreferenceController::class)->name('update-notification-preference');
        Route::post('/add/costumes', AddCostumeController::class)->name('add-costume');
        Route::post('/remove/costumes', RemoveCostumeController::class)->name('remove-costume');
        Route::post('/add/trooper/request', AddTrooperRequestController::class)->name('add-trooper-request');
        Route::post('/request/deletion', RequestDeletionController::class)->name('request-deletion');
        Route::delete('/cancel/deletion', CancelDeletionController::class)->name('cancel-deletion');

        Route::get('/visitor/renew', RenewVisitorController::class)->name('renew-visitor');
        Route::post('/visitor/renew', RenewVisitorMembershipController::class)->name('renew-visitor-membership');

        Route::get('/notices', NoticesController::class)->name('notices');
        Route::post('/notices-htmx/{notice}', NoticesSubmitHtmxController::class)->name('notices-htmx');

        //  needed a post name to get the middleware to work properly
        Route::get('/push-notifications', PushNotificationInboxController::class)->name('push-notifications');
        Route::post('/push-notifications/{notification}/read', PushNotificationReadController::class)->name('push-notifications.read');
        Route::delete('/push-notifications', PushNotificationClearController::class)->name('push-notifications.clear');

        Route::get('/setup', SetupController::class)->name('setup');
        Route::post('/setup', SetupSubmitController::class)->name('setup-submit');

        // XenForo linking required page
        Route::get('/xenforo/required', function (): View
        {
            $user = Auth::user();

            return view('pages.account.xenforo-required', [
                'user' => $user,
            ]);
        })->name('xenforo.required');

        // Optional: show current XenForo link status
        Route::get('/xenforo', function (): View
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
