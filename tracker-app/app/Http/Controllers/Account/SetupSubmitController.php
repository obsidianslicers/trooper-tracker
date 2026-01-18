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
 * Validates organization selections and member assignments, updates the trooper's email
 * and completion timestamp, and persists membership selections as TrooperAssignment records.
 */
class SetupSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to process trooper setup submission.
     *
     * Validates the setup request, updates the trooper's email and completion timestamp,
     * persists membership/assignment selections, flashes success feedback, and redirects.
     *
     * @param SetupRequest $request The validated setup form request.
     * @return RedirectResponse A redirect response to the account profile page.
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
