<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Commands\UpdateTrooperCommand;
use App\Features\Troopers\Commands\UpdateTrooperMembershipsCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Account\SetupRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Handles form submission for trooper organization membership setup.
 *
 * This controller follows the ADR pattern as an Action that:
 * - Validates organization selections via SetupRequest
 * - Updates trooper's email and marks setup as completed
 * - Persists organization membership/assignment selections as TrooperAssignment records
 * - Redirects to costume setup page (next step in onboarding)
 *
 * This is typically the first step in trooper onboarding after account approval,
 * where they configure which organizations they belong to and their roles within them.
 */
class SetupSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to process organization setup submission.
     *
     * Workflow:
     * 1. Retrieves the authenticated trooper from the request
     * 2. Dispatches UpdateTrooperCommand with complete_setup=true to mark onboarding progress
     * 3. Dispatches UpdateTrooperMembershipsCommand to persist organization selections
     * 4. Redirects to account.costumes route (next onboarding step)
     *
     * @param SetupRequest $request The validated setup form request containing organizations data
     * @return RedirectResponse Redirect to account.costumes route
     */
    public function __invoke(SetupRequest $request): RedirectResponse
    {
        $trooper = $request->user();

        $trooper_cmd = new UpdateTrooperCommand($trooper, $request->validated(), true);

        $this->bus->send($trooper_cmd);

        $memberships_cmd = new UpdateTrooperMembershipsCommand($trooper, $request->validated('organizations', []));

        $this->bus->send($memberships_cmd);

        return redirect()->route('account.costumes');
    }
}
