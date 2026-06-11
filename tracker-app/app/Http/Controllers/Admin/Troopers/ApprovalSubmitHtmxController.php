<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Features\Troopers\Commands\ApproveTrooperCommand;
use App\Http\Controllers\MagicBusController;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class TrooperApprovalSubmitHtmxController
 *
 * Handles the submission of a trooper's membership approval via an HTMX request.
 * This controller updates the trooper's status to 'Active', sends an approval email,
 * and returns a view fragment with a flash message in the response headers for HTMX to process.
 */
class ApprovalSubmitHtmxController extends MagicBusController
{
    /**
     * Handle the incoming request to approve a trooper's membership
     *
     * This method authorizes the action, updates the trooper's status to 'Active',
     * saves the model, and dispatches an approval email. It returns a view
     * with a custom 'X-Flash-Message' header for HTMX to display a success message.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Trooper  $trooper  The trooper pending approval
     * @return Response|View A response object containing the view and a custom header
     */
    public function __invoke(Request $request, Trooper $trooper): Response|View
    {
        $this->authorize('approve', $trooper);

        $approval_cmd = new ApproveTrooperCommand($trooper, true);

        $this->bus->send($approval_cmd);

        $trooper->load([
            'trooper_assignments' => function ($q) {
                $q->where(TrooperAssignment::IS_MEMBER, true)
                    ->with('organization.parent');
            },
        ]);

        $data = compact('trooper');

        $message = json_encode([
            'message' => "Trooper {$trooper->display_name} approved!",
            'type' => 'success',
        ]);

        return response()
            ->view('pages.admin.troopers.approval-card', $data)
            ->header('X-Flash-Message', $message);
    }
}
