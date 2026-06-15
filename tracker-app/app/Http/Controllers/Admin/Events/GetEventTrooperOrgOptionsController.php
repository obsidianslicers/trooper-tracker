<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\TrooperAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GetEventTrooperOrgOptionsController extends MagicBusController
{
    public function __invoke(Request $request, Event $event, EventTrooper $event_trooper): View
    {
        $this->authorize('update', $event);
        abort_if($event_trooper->event_shift->event_id !== $event->id, 404);

        $trooper_orgs = $event_trooper->trooper->organizations;
        $costume_id = $request->input('costume_id') !== null && $request->input('costume_id') !== ''
            ? (int) $request->input('costume_id')
            : null;

        $allowed_org_ids = $request->user()->is_administrator
            ? null
            : $request->user()->trooper_assignments()
                ->where(TrooperAssignment::IS_MODERATOR, true)
                ->pluck(TrooperAssignment::ORGANIZATION_ID)
                ->toArray();

        if ($costume_id !== null)
        {
            $costume = Costume::find($costume_id);

            if ($costume?->countsAsHandler())
            {
                $org_options = $this->memberCreditParentOrganizations($event_trooper)
                    ->when($allowed_org_ids !== null, fn ($c) => $c->whereIn('id', $allowed_org_ids))
                    ->values();

                $eligible_root_ids = $org_options->pluck('id')->all();
            }
            else
            {
                $eligible_root_ids = $event_trooper->trooper->trooper_costumes
                    ->filter(fn ($tc) => (int) $tc->organization_costume->costume_id === $costume_id)
                    ->map(fn ($tc) => (int) $tc->organization_costume->organization_id)
                    ->map(function ($id) use ($trooper_orgs) {
                        $org = $trooper_orgs->find($id);

                        return $org ? (int) explode(':', $org->node_path)[0] : (int) $id;
                    })
                    ->unique()
                    ->values()
                    ->all();

                $org_options = $trooper_orgs
                    ->whereIn('id', $eligible_root_ids)
                    ->when($allowed_org_ids !== null, fn ($c) => $c->whereIn('id', $allowed_org_ids))
                    ->values();
            }

            $is_new_costume = $costume_id !== $event_trooper->costume_id;
            if ($is_new_costume)
            {
                $credited_ids = $eligible_root_ids;
            }
            else
            {
                $credited_ids = collect($event_trooper->costume_organization_ids ?? [])
                    ->map(function ($id) use ($trooper_orgs) {
                        $org = $trooper_orgs->find($id);

                        return $org ? (int) explode(':', $org->node_path)[0] : (int) $id;
                    })
                    ->unique()
                    ->values()
                    ->all();
            }

            return view('pages.admin.events.inc.trooper-org-options', compact(
                'event_trooper',
                'org_options',
                'credited_ids'
            ));
        }

        $org_options = $this->memberCreditParentOrganizations($event_trooper)
            ->when($allowed_org_ids !== null, fn ($c) => $c->whereIn('id', $allowed_org_ids))
            ->values();

        $is_new_costume = $event_trooper->costume_id !== null;
        $credited_ids = $is_new_costume
            ? $org_options->pluck('id')->all()
            : collect($event_trooper->costume_organization_ids ?? [])
                ->map(function ($id) use ($trooper_orgs) {
                    $org = $trooper_orgs->find($id);

                    return $org ? (int) explode(':', $org->node_path)[0] : (int) $id;
                })
                ->unique()
                ->values()
                ->all();

        return view('pages.admin.events.inc.trooper-org-options', compact(
            'event_trooper',
            'org_options',
            'credited_ids'
        ));
    }

    /**
     * Handler and no-costume credit derives from memberships, not the currently
     * saved costume. This keeps HTMX option refreshes accurate before save.
     *
     * @return Collection<int, Organization>
     */
    private function memberCreditParentOrganizations(EventTrooper $event_trooper): Collection
    {
        return Organization::whereHas('trooper_assignments', fn ($q) => $q
            ->where(TrooperAssignment::TROOPER_ID, $event_trooper->trooper_id)
            ->where(TrooperAssignment::IS_MEMBER, true)
        )
            ->get()
            ->map(fn ($org) => $org->getPrimaryClub())
            ->unique('id')
            ->values();
    }
}
