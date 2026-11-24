<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\Commands\DenyTrooperMembershipRequest;
use App\Messages\Troopers\Commands\Membership\DenyTrooperMembership;
use App\Models\Trooper;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * class DenyTrooperMembershipController
 *
 * Handles the submission of a trooper's membership denial.
 * This controller updates the trooper's status to 'Denied', sends a denial email,
 * and answers with the approvals page and a toast.
 */
class DenyTrooperMembershipController extends Controller
{
    /**
     * Handle the incoming request to deny a trooper's membership
     *
     * This method authorizes the action, updates the trooper's status to 'Denied',
     * saves the model, and dispatches a denial email. It answers with the
     * approvals page and a success toast.
     *
     * @param  DenyTrooperMembershipRequest  $request  The incoming HTTP request
     * @param  Trooper  $trooper  The trooper pending denial
     * @return InertiaResponse|SymfonyResponse The approvals page
     */
    public function __invoke(DenyTrooperMembershipRequest $request, Trooper $trooper): InertiaResponse|SymfonyResponse
    {
        DenyTrooperMembership::call($request);

        FlashType::success(__('admin.Trooper membership denied successfully.'));

        return Inertia::render('admin/troopers/MembershipApprovals');
    }
}
