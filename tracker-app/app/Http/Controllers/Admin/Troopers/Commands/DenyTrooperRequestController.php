<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\Commands\DenyTrooperRequestRequest;
use App\Messages\Troopers\Commands\Membership\DenyTrooperRequest;
use App\Models\TrooperRequest;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * class DenyTrooperRequestController
 *
 * Handles the submission of a trooper's request denial.
 * This controller updates the trooper's status to 'Denied', sends a denial email,
 * and answers with the approvals page and a toast.
 */
class DenyTrooperRequestController extends Controller
{
    /**
     * Handle the incoming request to deny a trooper's request
     *
     * This method authorizes the action, updates the trooper's status to 'Denied',
     * saves the model, and dispatches a denial email. It answers with the
     * approvals page and a success toast.
     *
     * @param  DenyTrooperRequestRequest  $request  The incoming HTTP request
     * @param  TrooperRequest  $trooper_request  The trooper request pending denial
     * @return InertiaResponse|SymfonyResponse The approvals page
     */
    public function __invoke(
        DenyTrooperRequestRequest $request,
        TrooperRequest $trooper_request,
    ): InertiaResponse|SymfonyResponse {
        DenyTrooperRequest::call(
            $trooper_request,
            $request->validated('denial_reason'),
        );

        FlashType::success(__('admin.Trooper request denied successfully.'));

        return Inertia::render('admin/troopers/MembershipApprovals');
    }
}
