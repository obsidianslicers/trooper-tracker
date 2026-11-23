<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Enums\FlashType;
use App\Enums\ShiftAttendanceOutcome;
use App\Http\Controllers\PageController;
use App\Messages\Events\Commands\ConfirmShiftAttendance;
use App\Models\EventTrooper;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use ValueError;

/**
 * Handles club selection when confirming attendance for a multi-club costume.
 *
 * Called via POST after ConfirmShiftAttendance finds the trooper must choose
 * which top-level clubs receive credit before attendance is confirmed.
 * Accepts one or more parent organization IDs and narrows costume_organization_ids
 * to only the child orgs belonging to the selected parents.
 */
class CompleteShiftWithClubController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('events.Events'), 'events.list');
    }

    public function __invoke(Request $request, EventTrooper $event_trooper): View
    {
        try
        {
            $status = EventTrooperStatus::from(Crypt::decryptString($request->input('encrypted_status', '')));
        }
        catch (DecryptException|ValueError $e)
        {
            abort(404);
        }

        if ($status !== EventTrooperStatus::ATTENDED)
        {
            abort(404);
        }

        $submitted_ids = array_map('intval', (array) $request->input('organization_ids', []));

        if (empty($submitted_ids))
        {
            return view('pages.events.shift-complete-club-select', [
                'event_trooper' => $event_trooper,
                'organizations' => $event_trooper->getEligibleCreditParentOrganizations(),
                'encrypted_status' => $request->input('encrypted_status', ''),
                'error' => __('events.Please select at least one club to receive credit for this troop.'),
            ]);
        }

        $eligible_parent_ids = $event_trooper->getEligibleCreditParentOrganizations()->pluck('id');

        foreach ($submitted_ids as $id)
        {
            if (!$eligible_parent_ids->contains($id))
            {
                abort(422);
            }
        }

        $outcome = ConfirmShiftAttendance::call(
            event_trooper: $event_trooper,
            status: $status,
            parent_organization_ids: $submitted_ids,
        );

        $message = $outcome->message($status);

        if ($outcome === ShiftAttendanceOutcome::CLOSED)
        {
            FlashType::danger($message);
            $message = '';
        }

        return view('pages.events.shift-complete', compact('event_trooper', 'message'));
    }
}
