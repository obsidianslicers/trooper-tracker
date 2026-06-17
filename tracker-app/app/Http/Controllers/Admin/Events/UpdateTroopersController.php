<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Features\Events\Queries\GetTroopersForEventAdminQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use App\Models\Event;
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
        $allowed_org_ids = $request->user()->resolveModeratorOrgIds();

        $this->decorateEventTroopers($event_shifts, $allowed_org_ids);

        return view('pages.admin.events.troopers', compact('event', 'event_shifts'));
    }

    private function decorateEventTroopers(Collection $event_shifts, ?array $allowed_org_ids): void
    {
        $all = $event_shifts->flatMap(fn ($s) => $s->event_troopers);

        $costume_ids = $all->pluck('costume_id')->filter()->unique()->values()->all();
        $costumes_by_id = Costume::findMany($costume_ids)->keyBy('id');

        // Build costume options from the already-eager-loaded trooper_costumes rather than
        // firing one query per trooper. Handler/Command Staff costumes are always included.
        $approved_costume_ids = $all
            ->flatMap(fn ($et) => $et->trooper->trooper_costumes->pluck('organization_costume.costume_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $all_option_costumes = Costume::query()
            ->where(function ($q) use ($approved_costume_ids) {
                $q->whereIn(Costume::NAME, [Costume::COMMAND_STAFF, Costume::HANDLER]);
                if (!empty($approved_costume_ids))
                {
                    $q->orWhereIn('id', $approved_costume_ids);
                }
            })
            ->pluck('name', 'id');

        $handler_costume_ids = $all_option_costumes
            ->filter(fn ($name) => in_array($name, [Costume::COMMAND_STAFF, Costume::HANDLER], true))
            ->keys()
            ->toArray();

        $costume_options_by_trooper = $all->groupBy('trooper_id')
            ->map(function ($group) use ($all_option_costumes, $handler_costume_ids) {
                $trooper_costume_ids = $group->first()->trooper->trooper_costumes
                    ->pluck('organization_costume.costume_id')
                    ->filter()
                    ->unique()
                    ->toArray();

                $ids = array_unique(array_merge($trooper_costume_ids, $handler_costume_ids));

                return $all_option_costumes->only($ids)->toArray();
            });

        foreach ($event_shifts as $shift)
        {
            foreach ($shift->event_troopers as $event_trooper)
            {
                $event_trooper->costume_options = $costume_options_by_trooper->get($event_trooper->trooper_id, []);
                $costume = $costumes_by_id->get($event_trooper->costume_id);
                $event_trooper->org_options = $event_trooper->eligibleRootOrgsForAdmin($allowed_org_ids, $costume);
                $event_trooper->credited_checked_ids = $event_trooper->creditedRootOrgIds();
            }
        }
    }
}
