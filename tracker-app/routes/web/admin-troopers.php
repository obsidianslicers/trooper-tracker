<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Troopers\MembershipApprovalsController;
use App\Http\Controllers\Admin\Troopers\ApprovalSubmitHtmxController;
use App\Http\Controllers\Admin\Troopers\AuthorityController;
use App\Http\Controllers\Admin\Troopers\AuthoritySubmitController;
use App\Http\Controllers\Admin\Troopers\ChangesController;
use App\Http\Controllers\Admin\Troopers\CostumesController;
use App\Http\Controllers\Admin\Troopers\DenialSubmitHtmxController;
use App\Http\Controllers\Admin\Troopers\EventsController;
use App\Http\Controllers\Admin\Troopers\MemberLookupHtmxController;
use App\Http\Controllers\Admin\Troopers\MergeTroopersController;
use App\Http\Controllers\Admin\Troopers\MergeTroopersSubmitController;
use App\Http\Controllers\Admin\Troopers\TrooperRequestApproveHtmxController;
use App\Http\Controllers\Admin\Troopers\TrooperRequestDenyHtmxController;
use App\Http\Controllers\Admin\Troopers\ListController;
use App\Http\Controllers\Admin\Troopers\MembershipController;
use App\Http\Controllers\Admin\Troopers\GuardianController;
use App\Http\Controllers\Admin\Troopers\GuardianSubmitController;
use App\Http\Controllers\Admin\Troopers\MembershipRemoveController;
use App\Http\Controllers\Admin\Troopers\MembershipSubmitController;
use App\Http\Controllers\Admin\Troopers\ProfileController;
use App\Http\Controllers\Admin\Troopers\ProfileSubmitController;
use App\Http\Controllers\Admin\Troopers\RecruitController;
use App\Http\Controllers\Admin\Troopers\RecruitSubmitController;
use App\Http\Controllers\Admin\Troopers\MarkRipSubmitController;
use App\Http\Controllers\Admin\Troopers\UnmarkRipSubmitController;
use App\Http\Controllers\Admin\Troopers\UnvoidSubmitController;
use App\Http\Controllers\Admin\Troopers\VoidSubmitController;
use Illuminate\Support\Facades\Route;


//  ADMIN/TROOPERs
Route::prefix('admin/troopers')
    ->name('admin.troopers.')
    ->middleware(['auth', 'check.role:moderator,administrator'])
    ->group(function ()
    {
        Route::get('/', ListController::class)->name('list');

        Route::prefix('membership')
            ->name('membership.')
            ->group(function ()
            {
                Route::get('/approvals', MembershipApprovalsController::class)->name('approvals');
                Route::post('/approvals/{trooper}/approve', ApprovalSubmitHtmxController::class)->name('approve-htmx');
                Route::post('/approvals/{trooper}/deny', DenialSubmitHtmxController::class)->name('deny-htmx');
            });





        Route::get('/recruit', RecruitController::class)->name('recruit');
        Route::post('/recruit', RecruitSubmitController::class);

        Route::get('/merge', MergeTroopersController::class)->name('merge');
        Route::post('/merge', MergeTroopersSubmitController::class);

        Route::get('/trooper-requests/{trooper_request}/member-lookup', MemberLookupHtmxController::class)->name('trooper-requests.member-lookup');
        Route::post('/trooper-requests/{trooper_request}/approve', TrooperRequestApproveHtmxController::class)->name('trooper-requests.approve-htmx');
        Route::post('/trooper-requests/{trooper_request}/deny', TrooperRequestDenyHtmxController::class)->name('trooper-requests.deny-htmx');

        Route::get('/{trooper}', ProfileController::class)->name('profile');
        Route::post('/{trooper}', ProfileSubmitController::class);
        Route::get('/{trooper}/authority', AuthorityController::class)->name('authority');
        Route::post('/{trooper}/authority', AuthoritySubmitController::class);
        Route::get('/{trooper}/membership', MembershipController::class)->name('membership');
        Route::post('/{trooper}/membership', MembershipSubmitController::class);
        Route::post('/{trooper}/membership/{organization}/remove', MembershipRemoveController::class)->name('membership.remove');
        Route::get('/{trooper}/guardian', GuardianController::class)->name('guardian');
        Route::post('/{trooper}/guardian', GuardianSubmitController::class);
        Route::get('/{trooper}/events', EventsController::class)->name('events');
        Route::get('/{trooper}/costumes', CostumesController::class)->name('costumes');
        Route::get('/{trooper}/changes', ChangesController::class)->name('changes');
        Route::post('/{trooper}/void', VoidSubmitController::class)->name('void');
        Route::post('/{trooper}/unvoid', UnvoidSubmitController::class)->name('unvoid');
        Route::post('/{trooper}/rip', MarkRipSubmitController::class)->name('rip');
        Route::post('/{trooper}/unrip', UnmarkRipSubmitController::class)->name('unrip');
    });
