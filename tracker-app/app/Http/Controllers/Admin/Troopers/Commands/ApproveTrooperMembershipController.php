<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\Commands\ApproveTrooperMembershipRequest;
use App\Messages\Troopers\Commands\Membership\ApproveTrooperMembership;
use App\Models\Trooper;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * class ApproveTrooperMembershipController
 *
 * Handles the submission of a trooper's membership approval.
 * This controller updates the trooper's status to 'Active', sends an approval email,
 * and answers with the approvals page and a toast.
 */
class ApproveTrooperMembershipController extends Controller
{
    /**
     * Handle the incoming request to approve a trooper's membership
     *
     * This method authorizes the action, updates the trooper's status to 'Active',
     * saves the model, and dispatches an approval email. It answers with the
     * approvals page and a success toast.
     *
     * @param  ApproveTrooperMembershipRequest  $request  The incoming HTTP request
     * @param  Trooper  $trooper  The trooper pending approval
     * @return InertiaResponse|SymfonyResponse The approvals page
     */
    public function __invoke(ApproveTrooperMembershipRequest $request, Trooper $trooper): InertiaResponse|SymfonyResponse
    {
        ApproveTrooperMembership::call($request);

        FlashType::success(__('admin.Trooper membership approved successfully.'));

        return Inertia::render('admin/troopers/MembershipApprovals');
    }
}
