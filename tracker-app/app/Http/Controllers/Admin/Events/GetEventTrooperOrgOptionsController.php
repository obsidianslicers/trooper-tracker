<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
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

        $costume_id = $request->input('costume_id') !== null && $request->input('costume_id') !== ''
            ? (int) $request->input('costume_id')
            : null;
        $allowed_org_ids = $this->resolveAllowedOrgIds($request->user());

        [$org_options, $credited_ids] = $costume_id !== null
            ? $this->resolveWithCostume($event_trooper, $costume_id, $allowed_org_ids)
            : $this->resolveWithoutCostume($event_trooper, $allowed_org_ids);

        return view('pages.admin.events.inc.trooper-org-options', compact(
            'event_trooper',
            'org_options',
            'credited_ids'
        ));
    }

    private function resolveAllowedOrgIds(Trooper $auth_trooper): ?array
    {
        if ($auth_trooper->is_administrator)
        {
            return null;
        }

        return $auth_trooper->trooper_assignments()
            ->where(TrooperAssignment::IS_MODERATOR, true)
            ->pluck(TrooperAssignment::ORGANIZATION_ID)
            ->toArray();
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
            $org_options = $event_trooper->trooper->organizations
                ->whereIn('id', $eligible_root_ids)
                ->when($allowed_org_ids !== null, fn ($c) => $c->whereIn('id', $allowed_org_ids))
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
        $trooper_orgs = $event_trooper->trooper->organizations;

        return collect($event_trooper->costume_organization_ids ?? [])
            ->map(function ($id) use ($trooper_orgs) {
                $org = $trooper_orgs->find($id);

                return $org ? (int) explode(':', $org->node_path)[0] : (int) $id;
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
