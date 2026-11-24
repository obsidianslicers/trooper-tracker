<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\Commands\ApproveTrooperRequestRequest;
use App\Messages\Troopers\Commands\Membership\ApproveTrooperRequest;
use App\Models\TrooperRequest;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * class ApproveTrooperRequestController
 *
 * Handles the submission of a trooper's request approval.
 * This controller updates the trooper's status to 'Active', sends an approval email,
 * and answers with the approvals page and a toast.
 */
class ApproveTrooperRequestController extends Controller
{
    /**
     * Handle the incoming request to approve a trooper's request
     *
     * This method authorizes the action, updates the trooper's status to 'Active',
     * saves the model, and dispatches an approval email. It answers with the
     * approvals page and a success toast.
     *
     * @param  ApproveTrooperRequestRequest  $request  The incoming HTTP request
     * @param  TrooperRequest  $trooper_request  The trooper request pending approval
     * @return InertiaResponse|SymfonyResponse The approvals page
     */
    public function __invoke(
        ApproveTrooperRequestRequest $request,
        TrooperRequest $trooper_request,
    ): InertiaResponse|SymfonyResponse {
        ApproveTrooperRequest::call($trooper_request);

        FlashType::success(__('admin.Trooper request approved successfully.'));

        return Inertia::render('admin/troopers/MembershipApprovals');
    }
}
