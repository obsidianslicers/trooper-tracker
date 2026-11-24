<?php

declare(strict_types=1);

use App\Http\Controllers\Account\Commands\AddCostumeController;
use App\Http\Controllers\Account\Commands\CancelDeletionController;
use App\Http\Controllers\Account\Commands\ClearPushNotificationsController;
use App\Http\Controllers\Account\Commands\CreateTrooperRequestController;
use App\Http\Controllers\Account\Commands\MarkNoticeAsReadController;
use App\Http\Controllers\Account\Commands\MarkPushNotificationAsReadController;
use App\Http\Controllers\Account\Commands\RemoveCostumeController;
use App\Http\Controllers\Account\Commands\RenewVisitorMembershipController;
use App\Http\Controllers\Account\Commands\RequestDeletionController;
use App\Http\Controllers\Account\Commands\ResubmitDeniedTrooperController;
use App\Http\Controllers\Account\Commands\SetUpTrooperController;
use App\Http\Controllers\Account\Commands\UpdateNotificationFrequencyController;
use App\Http\Controllers\Account\Commands\UpdateNotificationPreferenceController;
use App\Http\Controllers\Account\Commands\UpdateOrganizationNotificationsController;
use App\Http\Controllers\Account\Commands\UpdateProfileController;
use App\Http\Controllers\Account\Commands\UpdatePushNotificationsController;
use App\Http\Controllers\Account\Pages\ListNoticesController;
use App\Http\Controllers\Account\Pages\ListPushNotificationsController;
use App\Http\Controllers\Account\Pages\ShowAccountController;
use App\Http\Controllers\Account\Pages\ShowDeniedMembershipController;
use App\Http\Controllers\Account\Pages\ShowPendingMembershipController;
use App\Http\Controllers\Account\Pages\ShowRenewVisitorFormController;
use App\Http\Controllers\Account\Pages\ShowSetupFormController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

//  ACCOUNT — denied/pending holding routes (auth only, no redirect middleware)
Route::prefix('account')
    ->name('account.')
    ->middleware('auth')
    ->group(function ()
    {
        Route::get('/denied', ShowDeniedMembershipController::class)->name('denied');
        Route::post('/denied/resubmit', ResubmitDeniedTrooperController::class)->name('denied.resubmit');
        Route::get('/pending', ShowPendingMembershipController::class)->name('pending');
    });

//  ACCOUNT
Route::prefix('account')
    ->name('account.')
    ->middleware(['auth', 'redirect.denied', 'redirect.pending'])
    ->group(function ()
    {
        Route::get('/', ShowAccountController::class)->name('index');
        Route::redirect('/profile', '/account');
        Route::post('/update/profile', UpdateProfileController::class)->name('update-profile');
        Route::post('/update/notifications/frequency', UpdateNotificationFrequencyController::class)->name('update-notification-frequency');
        Route::post('/update/notifications/push', UpdatePushNotificationsController::class)->name('update-push-notifications');
        Route::post('/update/notifications/organization', UpdateOrganizationNotificationsController::class)->name('update-organization-notifications');
        Route::post('/update/notifications/preference', UpdateNotificationPreferenceController::class)->name('update-notification-preference');
        Route::post('/add/costumes', AddCostumeController::class)->name('add-costume');
        Route::post('/remove/costumes', RemoveCostumeController::class)->name('remove-costume');
        Route::post('/add/trooper/request', CreateTrooperRequestController::class)->name('add-trooper-request');
        Route::post('/request/deletion', RequestDeletionController::class)->name('request-deletion');
        Route::delete('/cancel/deletion', CancelDeletionController::class)->name('cancel-deletion');

        Route::get('/visitor/renew', ShowRenewVisitorFormController::class)->name('renew-visitor');
        Route::post('/visitor/renew', RenewVisitorMembershipController::class)->name('renew-visitor-membership');

        Route::get('/notices', ListNoticesController::class)->name('notices');
        Route::post('/notices/{notice}/read', MarkNoticeAsReadController::class)->name('update-notice-as-read');

        //  needed a post name to get the middleware to work properly
        Route::get('/push-notifications', ListPushNotificationsController::class)->name('push-notifications');
        Route::post('/push-notifications/{notification}/read', MarkPushNotificationAsReadController::class)->name('push-notifications.read');
        Route::delete('/push-notifications', ClearPushNotificationsController::class)->name('push-notifications.clear');

        Route::get('/setup', ShowSetupFormController::class)->name('setup');
        Route::post('/setup', SetUpTrooperController::class)->name('setup-submit');
    });
