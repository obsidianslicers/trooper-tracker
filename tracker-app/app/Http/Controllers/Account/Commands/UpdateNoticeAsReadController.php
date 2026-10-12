<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Commands;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateNoticeAsReadRequest;
use App\Messages\Notices\Commands\UpdateNoticeAsRead;
use App\Models\Notice;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Handles form submission for marking a notice as read for the authenticated trooper.
 *
 * This controller validates the notice read request via UpdateNoticeAsReadRequest, dispatches
 * UpdateNoticeAsRead to persist the read status, and redirects back to the notices page.
 */
class UpdateNoticeAsReadController extends Controller
{
    /**
     * Handle the incoming request to mark a notice as read for the trooper.
     *
     * @param  UpdateNoticeAsReadRequest  $request  The validated notice read update request
     * @return InertiaResponse|SymfonyResponse Redirect to the notices page with success message
     */
    public function __invoke(UpdateNoticeAsReadRequest $request, Notice $notice): InertiaResponse|SymfonyResponse
    {
        $trooper = $request->user();

        UpdateNoticeAsRead::call(
            trooper: $trooper,
            notice: $notice
        );

        return Inertia::render('account/Notices');
    }
}
