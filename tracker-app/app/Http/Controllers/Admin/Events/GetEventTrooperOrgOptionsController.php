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

        $event_trooper->load('trooper.trooper_costumes.organization_costume', 'trooper.organizations');

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

        if ($costume?->countsAsHandler())
        {
            $org_options = $this->memberCreditParentOrganizations($event_trooper)
                ->when($allowed_org_ids !== null, fn ($c) => $c->whereIn('id', $allowed_org_ids))
                ->values();
            $eligible_root_ids = $org_options->pluck('id')->all();
        }
        else
        {
            $eligible_root_ids = $event_trooper->rootOrgIdsForCostume($costume_id);
            $org_options = Organization::whereIn('id', $eligible_root_ids)
                ->when($allowed_org_ids !== null, fn ($c) => $c->whereIn('id', $allowed_org_ids))
                ->orderBy(Organization::NAME)
                ->get()
                ->values();
        }

        $credited_ids = $is_new_costume ? $eligible_root_ids : $this->storedCreditedRootIds($event_trooper);

        return [$org_options, $credited_ids];
    }

    private function resolveWithoutCostume(EventTrooper $event_trooper, ?array $allowed_org_ids): array
    {
        $org_options = $this->memberCreditParentOrganizations($event_trooper)
            ->when($allowed_org_ids !== null, fn ($c) => $c->whereIn('id', $allowed_org_ids))
            ->values();

        $credited_ids = $event_trooper->costume_id !== null
            ? $org_options->pluck('id')->all()
            : $this->storedCreditedRootIds($event_trooper);

        return [$org_options, $credited_ids];
    }

    private function storedCreditedRootIds(EventTrooper $event_trooper): array
    {
        $credited_ids = collect($event_trooper->costume_organization_ids ?? []);
        $credit_orgs = Organization::findMany($credited_ids->all())->keyBy('id');

        return $credited_ids
            ->map(function ($id) use ($credit_orgs) {
                $org = $credit_orgs->get($id);

                return $org ? $org->getPrimaryClub()->id : (int) $id;
            })
            ->unique()
            ->values()
            ->all();
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
