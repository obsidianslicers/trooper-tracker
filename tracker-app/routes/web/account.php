<?php

declare(strict_types=1);

use App\Enums\OauthProvider;
use App\Http\Controllers\Account\IndexController;
use App\Http\Controllers\Account\UpdateProfileController;
use App\Http\Controllers\Account\UpdateNotificationFrequencyController;
use App\Http\Controllers\Account\ClubMembershipsController;
use App\Http\Controllers\Account\ClubMembershipsSubmitHtmxController;
use App\Http\Controllers\Account\DeletionCancelController;
use App\Http\Controllers\Account\DeletionRequestController;
use App\Http\Controllers\Account\CostumesController;
use App\Http\Controllers\Account\CostumesDeleteHtmxController;
use App\Http\Controllers\Account\CostumesSubmitHtmxController;
use App\Http\Controllers\Account\FriendsController;
use App\Http\Controllers\Account\MinorsController;
use App\Http\Controllers\Account\NoticesController;
use App\Http\Controllers\Account\NoticesSubmitHtmxController;
use App\Http\Controllers\Account\NotificationsController;
use App\Http\Controllers\Account\NotificationsSubmitController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\PushNotificationClearController;
use App\Http\Controllers\Account\PushNotificationInboxController;
use App\Http\Controllers\Account\PushNotificationReadController;
use App\Http\Controllers\Account\ProfileSubmitController;
use App\Http\Controllers\Account\DeniedController;
use App\Http\Controllers\Account\DeniedResubmitController;
use App\Http\Controllers\Account\PendingController;
use App\Http\Controllers\Account\SetupController;
use App\Http\Controllers\Account\SetupSubmitController;
use App\Http\Controllers\Account\VisitorRenewController;
use App\Http\Controllers\Account\VisitorRenewSubmitController;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\OauthLogin;

//  ACCOUNT — denied/pending holding routes (auth only, no redirect middleware)
Route::prefix('account')
    ->name('account.')
    ->middleware('auth')
    ->group(function ()
    {
        Route::get('/denied', DeniedController::class)->name('denied');
        Route::post('/denied/resubmit', DeniedResubmitController::class)->name('denied.resubmit');
        Route::get('/pending', PendingController::class)->name('pending');
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



        Route::get('/profile', ProfileController::class)->name('profile');
        Route::post('/profile', ProfileSubmitController::class);
        Route::get('/notifications', NotificationsController::class)->name('notifications');
        Route::post('/notifications', NotificationsSubmitController::class);
        Route::get('/notices', NoticesController::class)->name('notices');
        Route::post('/notices-htmx/{notice}', NoticesSubmitHtmxController::class)->name('notices-htmx');
        Route::get('/club-memberships', ClubMembershipsController::class)->name('club-memberships');
        Route::post('/club-memberships-htmx', ClubMembershipsSubmitHtmxController::class)->name('club-memberships-htmx');
        Route::get('/minors', MinorsController::class)->name('minors');
        Route::get('/friends', FriendsController::class)->name('friends');

        Route::get('/costumes', CostumesController::class)->name('costumes');
        Route::post('/costumes-htmx', CostumesSubmitHtmxController::class)->name('costumes-htmx');
        Route::delete('/costumes-htmx', CostumesDeleteHtmxController::class);

        //  needed a post name to get the middleware to work properly
        Route::get('/push-notifications', PushNotificationInboxController::class)->name('push-notifications');
        Route::post('/push-notifications/{notification}/read', PushNotificationReadController::class)->name('push-notifications.read');
        Route::delete('/push-notifications', PushNotificationClearController::class)->name('push-notifications.clear');

        Route::get('/setup', SetupController::class)->name('setup');
        Route::post('/setup', SetupSubmitController::class)->name('setup-submit');

        Route::get('/visitor-renew', VisitorRenewController::class)->name('visitor-renew');
        Route::post('/visitor-renew', VisitorRenewSubmitController::class)->name('visitor-renew-submit');

        Route::delete('/delete', DeletionRequestController::class)->name('delete');
        Route::delete('/delete/cancel', DeletionCancelController::class)->name('delete.cancel');

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
