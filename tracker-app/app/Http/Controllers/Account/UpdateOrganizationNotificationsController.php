<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Messages\Troopers\Commands\UpdateOrganizationNotifications;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateOrganizationNotificationsRequest;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Handles form submission for updating the authenticated trooper's organization notifications.
 *
 * This controller validates organization notifications data via UpdateOrganizationNotificationsRequest, dispatches
 * UpdateOrganizationNotifications to persist changes, and redirects back to the update profile page.
 */
class UpdateOrganizationNotificationsController extends Controller
{
    /**
     * Handle the incoming request to update the trooper's organization notifications.
     *
     * @param  UpdateOrganizationNotificationsRequest  $request  The validated organization notifications update request
     * @return  InertiaResponse|SymfonyResponse Redirect to the update organization notifications page with success message
     */
    public function __invoke(UpdateOrganizationNotificationsRequest $request): InertiaResponse|SymfonyResponse
    {
        $trooper = $request->user();

        UpdateOrganizationNotifications::call(
            trooper: $trooper,
            organization_ids: $request->validated('organization_ids'),
            enabled: $request->validated('enabled'),
        );

        return Inertia::render('account/Index');
    }
}
