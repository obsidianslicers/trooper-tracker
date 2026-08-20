<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateNotificationFrequencyRequest;
use App\Messages\Troopers\Commands\UpdateTrooperNotificationFrequency;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Handles form submission for updating the authenticated trooper's notification frequency.
 *
 * This controller validates notification frequency data via UpdateNotificationFrequencyRequest, dispatches
 * UpdateTrooperNotificationFrequency to persist changes, and redirects back to the update profile page.
 */
class UpdateNotificationFrequencyController extends Controller
{
    /**
     * Handle the incoming request to update the trooper's notification frequency.
     *
     * @param  UpdateNotificationFrequencyRequest  $request  The validated notification frequency update request
     * @return InertiaResponse|SymfonyResponse Redirect to the update notification frequency page with success message
     */
    public function __invoke(UpdateNotificationFrequencyRequest $request): InertiaResponse|SymfonyResponse
    {
        $trooper = $request->user();

        UpdateTrooperNotificationFrequency::call(
            trooper: $trooper,
            notification_frequency: $request->validated('notification_frequency'),
        );

        return Inertia::render('account/Index');
    }
}
