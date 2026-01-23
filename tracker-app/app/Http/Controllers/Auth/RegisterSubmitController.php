<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Features\Troopers\Commands\RegisterTrooperCommand;
use App\Features\Troopers\Commands\UpdateTrooperIdentifiersCommand;
use App\Features\Troopers\Commands\UpdateTrooperMembershipsCommand;
use App\Features\Troopers\Commands\UpdateTrooperNotificationsCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Auth\RegisterRequest;
use App\Jobs\SendTrooperRegisteredNotificationsJob;
use App\Mail\Auth\TrooperRegistered;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Mail;

/**
 * Handles the submission of the trooper registration form.
 *
 * This controller processes the complete registration flow by:
 * - Creating the trooper account with PENDING status
 * - Assigning organization memberships and notification preferences
 * - Linking OAuth accounts if applicable
 * - Sending confirmation email
 * - Redirecting to the thank you page
 */
class RegisterSubmitController extends MagicBusController
{
    /**
     * Handle the incoming registration request.
     *
     * Orchestrates the multi-step registration process including trooper creation,
     * organization assignments, notification preferences, and email confirmation.
     * The newly created trooper will have PENDING status until approved by an admin.
     *
     * @param RegisterRequest $request The validated registration form request.
     * @return RedirectResponse A redirect to the thank you page after successful registration.
     */
    public function __invoke(RegisterRequest $request): RedirectResponse
    {
        $register_cmd = new RegisterTrooperCommand($request->validated());

        $trooper = $this->bus->send($register_cmd);

        $organizations = $request->validated('organizations', []);

        $memberships = $this->getMemberships($organizations);
        $notifications = $this->getNotifications($organizations);

        $identifier_command = new UpdateTrooperIdentifiersCommand($trooper, $organizations);

        $this->bus->send($identifier_command);

        $membership_command = new UpdateTrooperMembershipsCommand($trooper, $memberships);

        $this->bus->send($membership_command);

        $notification_command = new UpdateTrooperNotificationsCommand($trooper, $notifications);

        $this->bus->send($notification_command);

        Mail::to($trooper->email)->queue(new TrooperRegistered());

        dispatch(new SendTrooperRegisteredNotificationsJob($trooper));

        $this->flash->success('Request submitted successfully! You will receive an e-mail when your request is approved or denied.');

        return redirect()->route('auth.thank-you');
    }

    /**
     * Extract notification preferences from the organizations data.
     *
     * Builds an array of organization IDs with notification flags enabled
     * for selected organizations and their associated regions/units.
     *
     * @param array $organizations The organizations data from the registration form
     * @return array Array keyed by organization ID with can_notify flags
     */
    private function getNotifications(array $organizations): array
    {
        $notifications = [];

        foreach ($organizations as $organization_id => $data)
        {
            if (!empty($data['selected']))
            {
                $notifications[$organization_id]['can_notify'] = true;

                if (isset($data['region_id']))
                {
                    $notifications[$data['region_id']]['can_notify'] = true;
                }

                if (isset($data['unit_id']))
                {
                    $notifications[$data['unit_id']]['can_notify'] = true;
                }
            }
        }

        return $notifications;
    }

    /**
     * Extract membership assignments from the organizations data.
     *
     * Determines which organization hierarchy level (region or unit) the trooper
     * should be marked as a member of based on the selected organization structure.
     * If a region has no units, membership is assigned to the region; otherwise,
     * membership is assigned to the selected unit.
     *
     * @param array $organizations The organizations data from the registration form
     * @return array Array keyed by organization ID with is_member flags
     */
    private function getMemberships(array $organizations): array
    {
        $memberships = [];

        foreach ($organizations as $organization_id => $data)
        {
            if (!empty($data['selected']))
            {
                $organization = Organization::find($organization_id);

                if ($organization)
                {
                    if (isset($data['region_id']))
                    {
                        $region = $organization->organizations()
                            ->ofTypeRegions()
                            ->firstWhere(Organization::ID, $data['region_id']);

                        if ($region->organizations()->count() == 0)
                        {
                            $memberships[$organization_id]['assignment'] = $data['region_id'];
                        }
                        elseif (isset($data['unit_id']))
                        {
                            $memberships[$organization_id]['assignment'] = $data['unit_id'];
                        }
                    }
                }
            }
        }

        return $memberships;
    }
}