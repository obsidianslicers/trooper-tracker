<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Awards\AssignTrooperRequest;
use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;

/**
 * Handles the submission of the form for assigning an award to a single trooper.
 *
 * Validates the assignment request, creates an AwardTrooper record linking the
 * trooper to the award for the specified date, and redirects to the award's
 * trooper list with a success message.
 */
class AssignTrooperSubmitController extends MagicBusController
{
    /**
     * Handle the request to assign the award to a single trooper.
     *
     * Validates the request, creates an AwardTrooper record for the selected
     * trooper on the specified date, and redirects with a success message.
     *
     * @param  AssignTrooperRequest  $request  The validated request containing the trooper to assign.
     * @param  Award  $award  The award being assigned.
     * @return RedirectResponse A redirect response to the award trooper list.
     */
    public function __invoke(AssignTrooperRequest $request, Award $award): RedirectResponse
    {
        $this->authorize('update', $award);

        $trooper_id = $request->validated('trooper_id');
        $award_date = $request->validated('award_date');

        $trooper = Trooper::findOrFail($trooper_id);

        $award_trooper = new AwardTrooper;

        $award_trooper->award_id = $award->id;
        $award_trooper->trooper_id = $trooper->id;
        $award_trooper->award_date = $award_date;

        $award_trooper->save();

        $this->flash->success('... and the Award goes to '.$trooper->display_name);

        return redirect()->route('admin.awards.list-troopers', $award);
    }
}
