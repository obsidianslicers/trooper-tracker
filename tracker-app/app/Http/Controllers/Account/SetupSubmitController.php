<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\SetupRequest;
use App\Models\Organization;
use App\Models\TrooperAssignment;
use App\Services\FlashMessageService;
use Illuminate\Http\RedirectResponse;

/**
 * Handles the form submission for updating the authenticated user's profile.
 */
class SetupSubmitController extends Controller
{
    /**
     * SetupSubmitController constructor.
     *
     * @param FlashMessageService $flash The service for creating flash messages.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Handle the incoming request to update the authenticated user's profile.
     *
     * This method validates the request data, updates the user's trooper record,
     * flashes a success message, and redirects back to the profile page.
     *
     * @param SetupRequest $request The validated profile form request.
     * @return RedirectResponse A redirect response to the account profile page.
     */
    public function __invoke(SetupRequest $request): RedirectResponse
    {
        dd($request->all());
        $trooper = $request->user();

        $trooper->email = $request->validated('email');

        $trooper->save();

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

        $this->flash->updated($trooper);

        return redirect()->route('account.profile');
    }
}
