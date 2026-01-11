<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\SetupRequest;
use App\Services\FlashMessageService;
use App\Services\Troopers\UpdateTrooperMembershipsCommand;
use App\Services\Troopers\UpdateTrooperProfileCommand;
use Illuminate\Http\RedirectResponse;

/**
 * Handles form submission for trooper organization membership setup.
 *
 * Validates organization selections and member assignments, updates the trooper's email
 * and completion timestamp, and persists membership selections as TrooperAssignment records.
 */
class SetupSubmitController extends Controller
{
    /**
     * Create a new SetupSubmitController instance.
     *
     * @param FlashMessageService $flash The flash message service for user feedback.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Handle the incoming request to process trooper setup submission.
     *
     * Validates the setup request, updates the trooper's email and completion timestamp,
     * persists membership/assignment selections, flashes success feedback, and redirects.
     *
     * @param SetupRequest $request The validated setup form request.
     * @param UpdateTrooperProfileCommand $update_profile The command to update trooper profile.
     * @param UpdateTrooperMembershipsCommand $update_memberships The command to update trooper memberships.
     * @return RedirectResponse A redirect response to the account profile page.
     */
    public function __invoke(
        SetupRequest $request,
        UpdateTrooperProfileCommand $update_profile,
        UpdateTrooperMembershipsCommand $update_memberships): RedirectResponse
    {
        $trooper = $request->user();

        $update_profile($trooper, $request->validated(), true);

        $update_memberships($trooper, $request->validated('organizations', []));

        $this->flash->updated($trooper);

        return redirect()->route('account.costumes');
    }
}
