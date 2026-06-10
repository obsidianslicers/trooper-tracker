<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Features\Troopers\Commands\RegisterTrooperCommand;
use App\Features\Troopers\Commands\SubmitJoinRequestCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Auth\RegisterRequest;
use App\Jobs\SendTrooperRegisteredNotificationsJob;
use App\Mail\Auth\GuardianAwareness;
use App\Mail\Auth\TrooperRegistered;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;
use Mail;

/**
 * Handles the submission of the trooper registration form.
 *
 * This controller processes the complete registration flow by:
 * - Creating the trooper account with PENDING status
 * - Submitting pending organization join requests
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
     * organization join requests and email confirmation.
     * The newly created trooper will have PENDING status until approved by an admin.
     *
     * @param  RegisterRequest  $request  The validated registration form request
     * @return RedirectResponse A redirect to the thank you page after successful registration
     */
    public function __invoke(RegisterRequest $request): RedirectResponse
    {
        $register_cmd = new RegisterTrooperCommand($request->validated());

        $trooper = $this->bus->send($register_cmd);

        $organizations = $request->validated('organizations', []);
        $this->submitJoinRequests($trooper, $organizations, $request->validated('account_type'));

        Mail::to($trooper->email)->queue(new TrooperRegistered);

        if ($trooper->is_minor)
        {
            Mail::to($trooper->guardian_email)->queue(new GuardianAwareness);
        }

        dispatch(new SendTrooperRegisteredNotificationsJob($trooper));

        $this->flash->success('Request submitted successfully! You will receive an e-mail when your request is approved or denied.');

        return redirect()->route('auth.thank-you');
    }

    /**
     * Submit pending join requests from the organizations selected during registration.
     *
     * @param  array<int, array<string, mixed>>  $organizations  The organizations data from the registration form
     */
    private function submitJoinRequests(Trooper $trooper, array $organizations, string $account_type): void
    {
        if ($account_type === 'handler')
        {
            return;
        }

        foreach ($organizations as $organization_id => $data)
        {
            if (empty($data['selected']))
            {
                continue;
            }

            $organization = Organization::find($organization_id);

            if (!$organization)
            {
                continue;
            }

            $requested_organization = $this->resolveJoinRequestOrganization($data, $organization, $account_type);

            if ($requested_organization !== null)
            {
                $identifier = isset($data['identifier']) ? trim((string) $data['identifier']) : null;

                $this->bus->send(new SubmitJoinRequestCommand(
                    $trooper,
                    $requested_organization,
                    $identifier === '' ? null : $identifier
                ));
            }
        }
    }

    private function resolveJoinRequestOrganization(array $data, Organization $organization, string $account_type): ?Organization
    {
        if ($account_type === 'visitor')
        {
            return $organization;
        }

        if (!isset($data['region_id']))
        {
            return null;
        }

        $region = $organization->organizations()
            ->ofTypeRegions()
            ->firstWhere(Organization::ID, $data['region_id']);

        if (!$region)
        {
            return null;
        }

        if ($region->organizations()->count() == 0)
        {
            return $region;
        }

        if (!isset($data['unit_id']))
        {
            return null;
        }

        return $region->organizations()
            ->ofTypeUnits()
            ->firstWhere(Organization::ID, $data['unit_id']);
    }
}
