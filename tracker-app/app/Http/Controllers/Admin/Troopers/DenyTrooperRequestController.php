<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\FlashType;
use App\Messages\Troopers\Commands\Membership\DenyTrooperRequest;
use App\Http\Controllers\Controller;
use App\Models\TrooperRequest;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use App\Http\Requests\Admin\Troopers\DenyTrooperRequestRequest;

/**
 * Class DenyTrooperRequestController
 *
 * Handles the submission of a trooper's membership denial via an HTMX request.
 * This controller updates the trooper's status to 'Denied', sends a denial email,
 * and returns a view fragment with a flash message in the response headers for HTMX to process.
 */
class DenyTrooperRequestController extends Controller
{
    /**
     * Handle the incoming request to deny a trooper's membership
     *
     * This method authorizes the action, updates the trooper's status to 'Denied',
     * saves the model, and dispatches a denial email. It returns a view
     * with a custom 'X-Flash-Message' header for HTMX to display a success message.
     *
     * @param  DenyTrooperRequestRequest  $request  The incoming HTTP request
     * @param  Trooper  $trooper  The trooper pending denial
     * @return InertiaResponse|SymfonyResponse A response object containing the view and a custom header
     */
    public function __invoke(
        DenyTrooperRequestRequest $request,
        TrooperRequest $trooper_request,
    ): InertiaResponse|SymfonyResponse {
        DenyTrooperRequest::call(
            $trooper_request,
            $request->validated('denial_reason'),
        );

        FlashType::success('Trooper membership denied successfully.');

        return Inertia::render('admin/troopers/MembershipApprovals');
    }
}
