<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Features\Events\Queries\GetTroopersForEventAdminQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UpdateTroopersController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Events', 'admin.events.list');
    }

    public function __invoke(Request $request, Event $event): View
    {
        $this->authorize('update', $event);

        $event_shifts = $this->bus->send(new GetTroopersForEventAdminQuery($event));
        $allowed_org_ids = $this->resolveAllowedOrgIds($request->user());

        $this->decorateEventTroopers($event_shifts, $allowed_org_ids);

        return view('pages.admin.events.troopers', compact('event', 'event_shifts'));
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

    private function decorateEventTroopers(Collection $event_shifts, ?array $allowed_org_ids): void
    {
        $all = $event_shifts->flatMap(fn ($s) => $s->event_troopers);

        $costume_ids = $all->pluck('costume_id')->filter()->unique()->values()->all();
        $costumes_by_id = Costume::findMany($costume_ids)->keyBy('id');

        $trooper_ids = $all->pluck('trooper_id')->unique()->values()->all();
        $costume_options_by_trooper = collect($trooper_ids)
            ->mapWithKeys(fn ($id) => [$id => Costume::forTrooper($id)->pluck('name', 'id')->toArray()]);

        foreach ($event_shifts as $shift)
        {
            foreach ($shift->event_troopers as $event_trooper)
            {
                $event_trooper->costume_options = $costume_options_by_trooper->get($event_trooper->trooper_id, []);
                $costume = $costumes_by_id->get($event_trooper->costume_id);
                $event_trooper->org_options = $this->resolveOrgOptions($event_trooper, $costume, $allowed_org_ids);
                $event_trooper->credited_checked_ids = $this->resolveCreditedCheckedIds($event_trooper);
            }
        }
    }

    private function resolveOrgOptions(EventTrooper $event_trooper, ?Costume $costume, ?array $allowed_org_ids): Collection
    {
        if ($event_trooper->costume_id !== null && $costume?->countsAsHandler())
        {
            return $event_trooper->getEligibleCreditParentOrganizations()
                ->when($allowed_org_ids !== null, fn ($c) => $c->whereIn('id', $allowed_org_ids))
                ->values();
        }

        if ($event_trooper->costume_id !== null)
        {
            $root_ids = $event_trooper->rootOrgIdsForCostume($event_trooper->costume_id);

            return $event_trooper->trooper->organizations
                ->whereIn('id', $root_ids)
                ->when($allowed_org_ids !== null, fn ($c) => $c->whereIn('id', $allowed_org_ids))
                ->values();
        }

        return $event_trooper->getEligibleCreditParentOrganizations()
            ->when($allowed_org_ids !== null, fn ($c) => $c->whereIn('id', $allowed_org_ids))
            ->values();
    }

    private function resolveCreditedCheckedIds(EventTrooper $event_trooper): array
    {
        $trooper_orgs = $event_trooper->trooper->organizations;

        if ($event_trooper->organization_id !== null)
        {
            $org = $trooper_orgs->find($event_trooper->organization_id);

            return [$org ? (int) explode(':', $org->node_path)[0] : $event_trooper->organization_id];
        }

        return collect($event_trooper->costume_organization_ids ?? [])
            ->map(function ($id) use ($trooper_orgs) {
                $org = $trooper_orgs->find($id);

                return $org ? (int) explode(':', $org->node_path)[0] : $id;
            })
            ->unique()
            ->values()
            ->all();
    }
}
