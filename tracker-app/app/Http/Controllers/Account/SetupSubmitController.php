<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\SetupRequest;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Services\FlashMessageService;
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
     * @return RedirectResponse A redirect response to the account profile page.
     */
    public function __invoke(SetupRequest $request): RedirectResponse
    {
        $trooper = $request->user();

        $this->updateTrooper($request, $trooper);
        $this->updateMemberships($request, $trooper);

        $this->flash->updated($trooper);

        return redirect()->route('account.costumes');
    }

    /**
     * Update the trooper's email and mark setup as completed.
     *
     * @param SetupRequest $request The validated setup request.
     * @param Trooper $trooper The trooper to update.
     * @return void
     */
    private function updateTrooper(SetupRequest $request, Trooper $trooper): void
    {
        $trooper->email = $request->validated('email');
        $trooper->setup_completed_at = now();

        $trooper->save();
    }

    /**
     * Persist trooper membership assignments based on selected organizations and hierarchy.
     *
     * For each selected organization, determines whether to assign the trooper to the unit
     * (if selected), region (if selected), or organization level, then creates or updates
     * the TrooperAssignment record with `is_member = true`.
     *
     * @param SetupRequest $request The validated setup request.
     * @param Trooper $trooper The trooper whose memberships are being updated.
     * @return void
     */
    private function updateMemberships(SetupRequest $request, Trooper $trooper): void
    {
        // Loop through selected organizations 
        $data = $request->input('organizations', []);

        foreach ($data as $organization_id => $organization_data)
        {
            if (!empty($organization_data['selected']))
            {
                $organization_assignment_id = $organization_id;
                $region_id = $organization_data['region_id'] ?? null;
                $unit_id = $organization_data['unit_id'] ?? null;

                if ($unit_id != null)
                {
                    $organization_assignment_id = $unit_id;
                }
                elseif ($region_id != null)
                {
                    $organization_assignment_id = $region_id;
                }

                //  Find assignment by unit if unit is selected
                $trooper_assignment = $trooper->trooper_assignments
                    ->filter(fn($t) => $t->organization_id == $organization_assignment_id)
                    ->first();

                if ($trooper_assignment == null)
                {
                    $trooper_assignment = new TrooperAssignment();
                    $trooper_assignment->trooper_id = $trooper->id;
                    $trooper_assignment->organization_id = $organization_assignment_id;
                }

                $trooper_assignment->is_member = true;
                $trooper_assignment->save();
            }
        }
    }
}
