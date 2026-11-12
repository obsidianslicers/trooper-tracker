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
use App\Http\Controllers\Admin\Troopers\Commands\MarkTrooperRipController;
use App\Http\Controllers\Admin\Troopers\Commands\MarkTrooperUntrustedController;
use App\Http\Controllers\Admin\Troopers\Commands\MergeTroopersSubmitController;
use App\Http\Controllers\Admin\Troopers\Commands\RecruitSubmitController;
use App\Http\Controllers\Admin\Troopers\Commands\RejectTroopCreditController;
use App\Http\Controllers\Admin\Troopers\Commands\RemoveMembershipController;
use App\Http\Controllers\Admin\Troopers\Commands\RequestPermissionsSubmitController;
use App\Http\Controllers\Admin\Troopers\Commands\RevokeCostumeController;
use App\Http\Controllers\Admin\Troopers\Commands\UndoRevokeCostumeController;
use App\Http\Controllers\Admin\Troopers\Commands\UnmarkTrooperRipController;
use App\Http\Controllers\Admin\Troopers\Commands\UnvoidTrooperController;
use App\Http\Controllers\Admin\Troopers\Commands\UpdateAuthorityController;
use App\Http\Controllers\Admin\Troopers\Commands\UpdateGuardianController;
use App\Http\Controllers\Admin\Troopers\Commands\UpdateMembershipController;
use App\Http\Controllers\Admin\Troopers\Commands\UpdateProfileController;
use App\Http\Controllers\Admin\Troopers\Commands\VoidTrooperController;
use App\Http\Controllers\Admin\Troopers\Pages\ApproveMembershipsController;
use App\Http\Controllers\Admin\Troopers\Pages\CostumeApprovalsController;
use App\Http\Controllers\Admin\Troopers\Pages\ListController;
use App\Http\Controllers\Admin\Troopers\Pages\LookupMembershipController;
use App\Http\Controllers\Admin\Troopers\Pages\LookupTrooperPermissionsController;
use App\Http\Controllers\Admin\Troopers\Pages\MergeTroopersController;
use App\Http\Controllers\Admin\Troopers\Pages\PermissionApprovalsController;
use App\Http\Controllers\Admin\Troopers\Pages\ProfileController;
use App\Http\Controllers\Admin\Troopers\Pages\RecruitController;
use App\Http\Controllers\Admin\Troopers\Pages\RequestPermissionsController;
use App\Http\Controllers\Admin\Troopers\Pages\TroopVerificationsController;
use Illuminate\Support\Facades\Route;

//  ADMIN/TROOPERs
Route::prefix('admin/troopers')
    ->name('admin.troopers.')
    ->middleware(['auth', 'check.role:moderator,staff_officer,system_owner'])
    ->group(function ()
    {
        Route::get('/', ListController::class)->name('list');

        Route::prefix('approvals')
            ->name('approvals.')
            ->group(function ()
            {
                Route::redirect('/', '/admin/troopers/approvals/index');
                Route::get('/index', ApproveMembershipsController::class)->name('index');
                Route::post('/approve/{trooper}', ApproveTrooperMembershipController::class)->name('approve-membership');
                Route::post('/deny/{trooper}', DenyTrooperMembershipController::class)->name('deny-membership');
                Route::post('/approve/{trooper_request}/request', ApproveTrooperRequestController::class)->name('approve-request');
                Route::post('/deny/{trooper_request}/request', DenyTrooperRequestController::class)->name('deny-request');

                Route::get('/requests/{trooper_request}/lookup-membership', LookupMembershipController::class)->name('lookup-membership');
            });

        Route::prefix('permissions')
            ->name('permissions.')
            ->group(function ()
            {
                Route::get('/', RequestPermissionsController::class)->name('request');
                Route::post('/', RequestPermissionsSubmitController::class);
                Route::get('/lookup/{trooper}', LookupTrooperPermissionsController::class)
                    ->name('lookup');

                Route::middleware('check.role:staff_officer,system_owner')
                    ->group(function ()
                    {
                        Route::get('/approvals', PermissionApprovalsController::class)
                            ->name('approvals');
                        Route::post(
                            '/{permission_request}/approve',
                            ApprovePermissionsController::class
                        )->name('approve');
                        Route::post('/{permission_request}/deny', DenyPermissionsController::class)
                            ->name('deny');
                    });
            });

        Route::get('/costume-approvals', CostumeApprovalsController::class)->name('costume-approvals');
        Route::post('/costumes/{trooper_costume}/approve', ApproveCostumeController::class)
            ->name('costumes.approve');
        Route::post('/costumes/{trooper_costume}/deny', DenyCostumeController::class)
            ->name('costumes.deny');
        Route::post('/costumes/{trooper_costume}/revoke', RevokeCostumeController::class)
            ->name('costumes.revoke');
        Route::post('/costumes/{trooper_costume}/undo-revoke', UndoRevokeCostumeController::class)
            ->name('costumes.undo-revoke')
            ->withTrashed();

        Route::get('/troop-verifications', TroopVerificationsController::class)
            ->name('troop-verifications');
        Route::post(
            '/troop-verifications/{event_trooper}/{organization}/approve',
            ApproveTroopCreditController::class
        )->name('troop-verifications.approve');
        Route::post(
            '/troop-verifications/{event_trooper}/{organization}/reject',
            RejectTroopCreditController::class
        )->name('troop-verifications.reject');

        Route::get('/recruit', RecruitController::class)->name('recruit');
        Route::post('/recruit', RecruitSubmitController::class);

        Route::middleware('check.role:system_owner')
            ->group(function ()
            {
                Route::get('/merge', MergeTroopersController::class)->name('merge');
                Route::post('/merge', MergeTroopersSubmitController::class);
            });

        Route::get('/{trooper}', ProfileController::class)->name('profile');
        Route::post('/{trooper}/profile', UpdateProfileController::class)->name('update-profile');
        Route::post('/{trooper}/authority', UpdateAuthorityController::class)->name('update-authority');
        Route::post('/{trooper}/memberships/{organization}', UpdateMembershipController::class)->name('update-membership');
        Route::post('/{trooper}/memberships/{organization}/remove', RemoveMembershipController::class)->name('remove-membership');
        Route::post('/{trooper}/guardian', UpdateGuardianController::class)->name('update-guardian');
        Route::post('/{trooper}/void', VoidTrooperController::class)->name('void');
        Route::post('/{trooper}/unvoid', UnvoidTrooperController::class)->name('unvoid');
        Route::post('/{trooper}/rip', MarkTrooperRipController::class)->name('rip');
        Route::post('/{trooper}/unrip', UnmarkTrooperRipController::class)->name('unrip');
        Route::post('/{trooper}/untrusted', MarkTrooperUntrustedController::class)->name('untrusted');
        Route::post('/{trooper}/trusted', ClearTrooperUntrustedController::class)->name('trusted');
    });
