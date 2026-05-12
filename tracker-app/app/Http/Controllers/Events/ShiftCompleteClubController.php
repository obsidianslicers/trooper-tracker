<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\UpdateEventTrooperCommand;
use App\Http\Controllers\MagicBusController;
use App\Models\EventTrooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles club selection when confirming attendance for a multi-club costume.
 *
 * Called via POST after ShiftCompleteController determines the trooper must
 * choose which top-level clubs receive credit before attendance is confirmed.
 * Accepts one or more parent organization IDs and narrows costume_organization_ids
 * to only the child orgs belonging to the selected parents.
 */
class ShiftCompleteClubController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Events', 'events.list');
    }

    public function __invoke(Request $request, EventTrooper $event_trooper): View
    {
        $submitted_ids = array_map('intval', (array) $request->input('organization_ids', []));

        if (empty($submitted_ids))
        {
            abort(422);
        }

        $eligible_parent_ids = $event_trooper->getEligibleCreditParentOrganizations()->pluck('id');

        foreach ($submitted_ids as $id)
        {
            if (!$eligible_parent_ids->contains($id))
            {
                abort(422);
            }
        }

        if (!$event_trooper->event_shift->event->can_update_trooper_status)
        {
            $this->flash->danger('Status updates for this event are no longer permitted. Please reach out to your administrator for further help.');

            $message = '';

            return view('pages.events.shift-complete', compact('event_trooper', 'message'));
        }

        $child_org_ids = $event_trooper->childOrgIdsForSelectedParents($submitted_ids);

        $valid_data = [
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
            EventTrooper::COSTUME_ORGANIZATION_IDS => $child_org_ids,
            // organization_id is intentionally not set — multi-club credit flows via costume_organization_ids
        ];

        $this->bus->send(new UpdateEventTrooperCommand($event_trooper, $valid_data));

        $message = 'Excellent work trooper! Imperial records now reflect your heroic presence.';

        return view('pages.events.shift-complete', compact('event_trooper', 'message'));
    }
}
