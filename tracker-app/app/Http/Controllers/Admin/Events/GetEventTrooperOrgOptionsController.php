<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventTrooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class GetEventTrooperOrgOptionsController extends MagicBusController
{
    public function __invoke(Request $request, Event $event, EventTrooper $event_trooper): View
    {
        $this->authorize('update', $event);
        abort_if($event_trooper->event_shift->event_id !== $event->id, 404);

        $event_trooper->load('trooper.trooper_costumes.organization_costume', 'trooper.organizations', 'costume');

        $costume_id = $request->input('costume_id') !== null && $request->input('costume_id') !== ''
            ? (int) $request->input('costume_id')
            : null;
        $allowed_org_ids = $request->user()->resolveModeratorOrgIds();

        [$org_options, $credited_ids] = $costume_id !== null
            ? $this->resolveWithCostume($event_trooper, $costume_id, $allowed_org_ids)
            : $this->resolveWithoutCostume($event_trooper, $allowed_org_ids);

        return view('pages.admin.events.inc.trooper-org-options', compact(
            'event_trooper',
            'org_options',
            'credited_ids'
        ));
    }

    private function resolveWithCostume(EventTrooper $event_trooper, int $costume_id, ?array $allowed_org_ids): array
    {
        $costume = Costume::find($costume_id);
        $is_new_costume = $costume_id !== $event_trooper->costume_id;

        $org_options = $event_trooper->eligibleRootOrgsForAdmin($allowed_org_ids, $costume);
        $credited_ids = $is_new_costume ? $org_options->pluck('id')->all() : $event_trooper->creditedRootOrgIds();

        return [$org_options, $credited_ids];
    }

    private function resolveWithoutCostume(EventTrooper $event_trooper, ?array $allowed_org_ids): array
    {
        $org_options = $event_trooper->eligibleRootOrgsForAdmin($allowed_org_ids);

        $credited_ids = $event_trooper->costume_id !== null
            ? $org_options->pluck('id')->all()
            : $event_trooper->creditedRootOrgIds();

        return [$org_options, $credited_ids];
    }
}
