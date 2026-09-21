<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\ApproveTrooperRequestRequest;
use App\Messages\Troopers\Commands\Membership\ApproveTrooperRequest;
use App\Models\TrooperRequest;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Class ApproveTrooperRequestController
 *
 * Handles the submission of a trooper's request approval via an HTMX request.
 * This controller updates the trooper's status to 'Active', sends an approval email,
 * and returns a view fragment with a flash message in the response headers for HTMX to process.
 */
class ApproveTrooperRequestController extends Controller
{
    /**
     * Handle the incoming request to approve a trooper's request
     *
     * This method authorizes the action, updates the trooper's status to 'Active',
     * saves the model, and dispatches an approval email. It returns a view
     * with a custom 'X-Flash-Message' header for HTMX to display a success message.
     *
     * @param  ApproveTrooperRequestRequest  $request  The incoming HTTP request
     * @param  TrooperRequest  $trooper_request  The trooper request pending approval
     * @return InertiaResponse|SymfonyResponse A response object containing the view and a custom header
     */
    public function __invoke(
        ApproveTrooperRequestRequest $request,
        TrooperRequest $trooper_request,
    ): InertiaResponse|SymfonyResponse {
        ApproveTrooperRequest::call($trooper_request);

        FlashType::success('Trooper request approved successfully.');

        return Inertia::render('admin/troopers/MembershipApprovals');
    }
}
