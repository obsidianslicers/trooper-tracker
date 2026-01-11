<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Mail\Auth\TrooperRegistered;
use App\Models\Organization;
use App\Services\FlashMessageService;
use App\Services\Troopers\AssignTrooperIdentifiersCommand;
use App\Services\Troopers\AssignTrooperMembershipsCommand;
use App\Services\Troopers\AssignTrooperNotificationsCommand;
use App\Services\Troopers\RegisterTrooperCommand;
use Illuminate\Http\RedirectResponse;
use Mail;

/**
 * Handles the submission of the user registration form.
 */
class RegisterSubmitController extends Controller
{
    /**
     * @param FlashMessageService $flash The flash message service.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Handle the incoming registration request.
     *
     * @param RegisterRequest $request The validated registration form request.
     * @return RedirectResponse A redirect response back to the registration page with a status message or errors.
     */
    public function __invoke(
        RegisterRequest $request,
        RegisterTrooperCommand $register_trooper,
        AssignTrooperIdentifiersCommand $assign_identifiers,
        AssignTrooperNotificationsCommand $assign_notifications,
        AssignTrooperMembershipsCommand $assign_memberships): RedirectResponse
    {
        $auth_user_id = null;

        $trooper = $register_trooper($request->validated());

        $organizations = $request->validated('organizations', []);

        $memberships = $this->getMemberships($organizations);
        $notifications = $this->getNotifications($organizations);

        $assign_identifiers($trooper, $organizations);
        $assign_memberships($trooper, $memberships);
        $assign_notifications($trooper, $notifications);

        Mail::to($trooper->email)->queue(new TrooperRegistered());

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
                    $notifications[$data['region_id']] = true;
                }

                if (isset($data['unit_id']))
                {
                    $notifications[$data['unit_id']] = true;
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
                            $memberships[$data['region_id']]['is_member'] = true;
                        }
                        elseif (isset($data['unit_id']))
                        {
                            $memberships[$data['unit_id']]['is_member'] = true;
                        }
                    }
                }
            }
        }

        return $memberships;
    }
}