<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Commands;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateNoticeReadRequest;
use App\Messages\Troopers\Commands\UpdateNoticeRead;
use App\Models\Base\Notice;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Handles form submission for updating the authenticated trooper's profile.
 *
 * This controller validates profile data via UpdateProfileRequest, dispatches
 * UpdateTrooperProfile to persist changes, and redirects back to the update profile page.
 */
class UpdateNoticeReadController extends Controller
{
    /**
     * Handle the incoming request to mark a notice as read for the trooper.
     *
     * @param  UpdateNoticeReadRequest  $request  The validated notice read update request
     * @return InertiaResponse|SymfonyResponse Redirect to the notices page with success message
     */
    public function __invoke(UpdateNoticeReadRequest $request, Notice $notice): InertiaResponse|SymfonyResponse
    {
        $trooper = $request->user();

        UpdateNoticeRead::call(
            trooper: $trooper,
            notice: $notice
        );

        return Inertia::render('account/Notices');
    }
}
