<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Troopers\ApprovalListController;
use App\Http\Controllers\Admin\Troopers\ApprovalSubmitHtmxController;
use App\Http\Controllers\Admin\Troopers\AuthorityController;
use App\Http\Controllers\Admin\Troopers\AuthoritySubmitController;
use App\Http\Controllers\Admin\Troopers\ChangesController;
use App\Http\Controllers\Admin\Troopers\CostumesController;
use App\Http\Controllers\Admin\Troopers\DenialSubmitHtmxController;
use App\Http\Controllers\Admin\Troopers\EventsController;
use App\Http\Controllers\Admin\Troopers\JoinRequestApproveHtmxController;
use App\Http\Controllers\Admin\Troopers\JoinRequestDenyHtmxController;
use App\Http\Controllers\Admin\Troopers\ListController;
use App\Http\Controllers\Admin\Troopers\MembershipController;
use App\Http\Controllers\Admin\Troopers\MembershipSubmitController;
use App\Http\Controllers\Admin\Troopers\ProfileController;
use App\Http\Controllers\Admin\Troopers\ProfileSubmitController;
use App\Http\Controllers\Admin\Troopers\RecruitController;
use App\Http\Controllers\Admin\Troopers\RecruitSubmitController;
use Illuminate\Support\Facades\Route;


//  ADMIN/TROOPERs
Route::prefix('admin/troopers')
    ->name('admin.troopers.')
    ->middleware(['auth', 'check.role:moderator,administrator'])
    ->group(function ()
    {
        Route::get('/', ListController::class)->name('list');
        Route::get('/approvals', ApprovalListController::class)->name('approvals');
        Route::post('/approvals/{trooper}/approve', ApprovalSubmitHtmxController::class)->name('approve-htmx');
        Route::post('/approvals/{trooper}/deny', DenialSubmitHtmxController::class)->name('deny-htmx');

        Route::get('/recruit', RecruitController::class)->name('recruit');
        Route::post('/recruit', RecruitSubmitController::class);

        Route::post('/join-requests/{join_request}/approve', JoinRequestApproveHtmxController::class)->name('join-requests.approve-htmx');
        Route::post('/join-requests/{join_request}/deny', JoinRequestDenyHtmxController::class)->name('join-requests.deny-htmx');

        Route::get('/{trooper}', ProfileController::class)->name('profile');
        Route::post('/{trooper}', ProfileSubmitController::class);
        Route::get('/{trooper}/authority', AuthorityController::class)->name('authority');
        Route::post('/{trooper}/authority', AuthoritySubmitController::class);
        Route::get('/{trooper}/membership', MembershipController::class)->name('membership');
        Route::post('/{trooper}/membership', MembershipSubmitController::class);
        Route::get('/{trooper}/events', EventsController::class)->name('events');
        Route::get('/{trooper}/costumes', CostumesController::class)->name('costumes');
        Route::get('/{trooper}/changes', ChangesController::class)->name('changes');
    });
