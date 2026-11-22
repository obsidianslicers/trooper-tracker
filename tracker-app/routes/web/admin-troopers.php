<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Troopers\Commands\ApproveCostumeController;
use App\Http\Controllers\Admin\Troopers\Commands\ApprovePermissionsController;
use App\Http\Controllers\Admin\Troopers\Commands\ApproveTroopCreditController;
use App\Http\Controllers\Admin\Troopers\Commands\ApproveTrooperMembershipController;
use App\Http\Controllers\Admin\Troopers\Commands\ApproveTrooperRequestController;
use App\Http\Controllers\Admin\Troopers\Commands\ClearTrooperUntrustedController;
use App\Http\Controllers\Admin\Troopers\Commands\DenyCostumeController;
use App\Http\Controllers\Admin\Troopers\Commands\DenyPermissionsController;
use App\Http\Controllers\Admin\Troopers\Commands\DenyTrooperMembershipController;
use App\Http\Controllers\Admin\Troopers\Commands\DenyTrooperRequestController;
use App\Http\Controllers\Admin\Troopers\Commands\DestroyTrooperMembershipController;
use App\Http\Controllers\Admin\Troopers\Commands\MarkTrooperRipController;
use App\Http\Controllers\Admin\Troopers\Commands\MarkTrooperUntrustedController;
use App\Http\Controllers\Admin\Troopers\Commands\MergeTroopersController;
use App\Http\Controllers\Admin\Troopers\Commands\RecruitTrooperController;
use App\Http\Controllers\Admin\Troopers\Commands\RejectTroopCreditController;
use App\Http\Controllers\Admin\Troopers\Commands\RevokeCostumeController;
use App\Http\Controllers\Admin\Troopers\Commands\StorePermissionRequestController;
use App\Http\Controllers\Admin\Troopers\Commands\UnmarkTrooperRipController;
use App\Http\Controllers\Admin\Troopers\Commands\UnrevokeCostumeController;
use App\Http\Controllers\Admin\Troopers\Commands\UnvoidTrooperController;
use App\Http\Controllers\Admin\Troopers\Commands\UpdateTrooperAuthorityController;
use App\Http\Controllers\Admin\Troopers\Commands\UpdateTrooperGuardianController;
use App\Http\Controllers\Admin\Troopers\Commands\UpdateTrooperMembershipController;
use App\Http\Controllers\Admin\Troopers\Commands\UpdateTrooperProfileController;
use App\Http\Controllers\Admin\Troopers\Commands\VoidTrooperController;
use App\Http\Controllers\Admin\Troopers\Pages\CreatePermissionRequestController;
use App\Http\Controllers\Admin\Troopers\Pages\IndexCostumeApprovalsController;
use App\Http\Controllers\Admin\Troopers\Pages\IndexPermissionApprovalsController;
use App\Http\Controllers\Admin\Troopers\Pages\IndexTrooperApprovalsController;
use App\Http\Controllers\Admin\Troopers\Pages\IndexTroopersController;
use App\Http\Controllers\Admin\Troopers\Pages\IndexTroopVerificationsController;
use App\Http\Controllers\Admin\Troopers\Pages\LookUpMembershipController;
use App\Http\Controllers\Admin\Troopers\Pages\LookUpTrooperPermissionsController;
use App\Http\Controllers\Admin\Troopers\Pages\ShowMergeTroopersFormController;
use App\Http\Controllers\Admin\Troopers\Pages\ShowRecruitTrooperFormController;
use App\Http\Controllers\Admin\Troopers\Pages\ShowTrooperController;
use Illuminate\Routing\RedirectController;
use Illuminate\Support\Facades\Route;

//  ADMIN/TROOPERS
Route::prefix('admin/troopers')
    ->name('admin.troopers.')
    ->middleware(['auth', 'check.role:moderator,staff_officer,system_owner'])
    ->group(function ()
    {
        // TODO remove after 2026-12-22: emails sent before the admin routes were renamed link here
        Route::permanentRedirect('/approvals/index', '/admin/troopers/approvals');
        Route::get('/permissions', RedirectController::class)
            ->defaults('destination', '/admin/troopers/permissions/create')
            ->defaults('status', 301);

        Route::get('/', IndexTroopersController::class)->name('index');

        Route::prefix('approvals')
            ->name('approvals.')
            ->group(function ()
            {
                Route::get('/', IndexTrooperApprovalsController::class)->name('index');
                Route::post('/approve/{trooper}', ApproveTrooperMembershipController::class)->name('approve-membership');
                Route::post('/deny/{trooper}', DenyTrooperMembershipController::class)->name('deny-membership');
                Route::post('/approve/{trooper_request}/request', ApproveTrooperRequestController::class)->name('approve-request');
                Route::post('/deny/{trooper_request}/request', DenyTrooperRequestController::class)->name('deny-request');

                Route::get('/requests/{trooper_request}/lookup-membership', LookUpMembershipController::class)
                    ->name('lookup-membership');
            });

        Route::prefix('permissions')
            ->name('permissions.')
            ->group(function ()
            {
                Route::get('/create', CreatePermissionRequestController::class)->name('create');
                Route::post('/', StorePermissionRequestController::class)->name('store');
                Route::get('/lookup/{trooper}', LookUpTrooperPermissionsController::class)
                    ->name('lookup');

                Route::middleware('check.role:staff_officer,system_owner')
                    ->group(function ()
                    {
                        Route::get('/approvals', IndexPermissionApprovalsController::class)
                            ->name('approvals.index');
                        Route::post(
                            '/{permission_request}/approve',
                            ApprovePermissionsController::class
                        )->name('approve');
                        Route::post('/{permission_request}/deny', DenyPermissionsController::class)
                            ->name('deny');
                    });
            });

        Route::get('/costume-approvals', IndexCostumeApprovalsController::class)->name('costume-approvals.index');
        Route::post('/costumes/{trooper_costume}/approve', ApproveCostumeController::class)
            ->name('costumes.approve');
        Route::post('/costumes/{trooper_costume}/deny', DenyCostumeController::class)
            ->name('costumes.deny');
        Route::post('/costumes/{trooper_costume}/revoke', RevokeCostumeController::class)
            ->name('costumes.revoke');
        Route::post('/costumes/{trooper_costume}/unrevoke', UnrevokeCostumeController::class)
            ->name('costumes.unrevoke')
            ->withTrashed();

        Route::get('/troop-verifications', IndexTroopVerificationsController::class)
            ->name('troop-verifications.index');
        Route::post(
            '/troop-verifications/{event_trooper}/{organization}/approve',
            ApproveTroopCreditController::class
        )->name('troop-verifications.approve');
        Route::post(
            '/troop-verifications/{event_trooper}/{organization}/reject',
            RejectTroopCreditController::class
        )->name('troop-verifications.reject');

        Route::get('/recruit', ShowRecruitTrooperFormController::class)->name('recruit.create');
        Route::post('/recruit', RecruitTrooperController::class)->name('recruit.store');

        Route::middleware('check.role:system_owner')
            ->group(function ()
            {
                Route::get('/merge', ShowMergeTroopersFormController::class)->name('merge.create');
                Route::post('/merge', MergeTroopersController::class)->name('merge.store');
            });

        Route::get('/{trooper}', ShowTrooperController::class)->name('show');
        Route::post('/{trooper}/profile', UpdateTrooperProfileController::class)->name('profile.update');
        Route::post('/{trooper}/authority', UpdateTrooperAuthorityController::class)->name('authority.update');
        Route::post('/{trooper}/memberships/{organization}', UpdateTrooperMembershipController::class)
            ->name('memberships.update');
        Route::post('/{trooper}/memberships/{organization}/delete', DestroyTrooperMembershipController::class)
            ->name('memberships.destroy');
        Route::post('/{trooper}/guardian', UpdateTrooperGuardianController::class)->name('guardian.update');
        Route::post('/{trooper}/void', VoidTrooperController::class)->name('void');
        Route::post('/{trooper}/unvoid', UnvoidTrooperController::class)->name('unvoid');
        Route::post('/{trooper}/rip', MarkTrooperRipController::class)->name('rip');
        Route::post('/{trooper}/unrip', UnmarkTrooperRipController::class)->name('unrip');
        Route::post('/{trooper}/untrusted', MarkTrooperUntrustedController::class)->name('untrusted');
        Route::post('/{trooper}/trusted', ClearTrooperUntrustedController::class)->name('trusted');
    });
