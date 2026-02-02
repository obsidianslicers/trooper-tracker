<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Awards\AssignTroopersRequest;
use App\Models\Award;
use App\Models\AwardTrooper;
use Illuminate\Http\RedirectResponse;

/**
 * Class AssignTroopersSubmitController
 *
 * Handles the submission of the form for assigning an award to troopers.
 */
class AssignTroopersSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to assign the award to troopers.
     *
     * Validates the request, creates AwardTrooper records for the selected troopers,
     * saves them, and then redirects with a success message.
     *
     * @param  AssignTroopersRequest  $request  The validated request containing the troopers to assign.
     * @param  Award  $award  The award being assigned.
     * @return RedirectResponse A redirect response to the awards list-troopers.
     */
    public function __invoke(AssignTroopersRequest $request, Award $award): RedirectResponse
    {
        $this->authorize('update', $award);

        $trooperIds = $request->validated('trooper_ids', []);
        $awardDate = $request->validated('award_date');

        foreach ($trooperIds as $trooperId)
        {
            // Check if already assigned
            $existing = AwardTrooper::where('award_id', $award->id)
                ->where('trooper_id', $trooperId)
                ->first();

            if ($existing)
            {
                // Update the award date for existing assignments
                $existing->update(['award_date' => $awardDate]);
            }
            else
            {
                // Create new assignment
                AwardTrooper::create([
                    'award_id' => $award->id,
                    'trooper_id' => $trooperId,
                    'award_date' => $awardDate,
                ]);
            }
        }

        $this->flash->success('Award assignments updated successfully');

        return redirect()->route('admin.awards.list-troopers', $award);
    }
}
