<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Features\Events\Queries\GetTroopersForEventAdminQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use App\Models\Event;
use App\Models\TrooperAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the trooper roster management form for an event.
 *
 * Provides administrators and moderators with a form to manage trooper
 * registrations and status updates for an event. Displays troopers
 * organized by shifts.
 */
class UpdateTroopersController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Events', 'admin.events.list');
    }

    /**
     * Displays the trooper roster management form
     *
     * Authorizes administrative permission to update the event via policy.
     * Retrieves event shifts with enriched trooper and costume data via
     * GetTroopersForEventAdminQuery and renders the roster management view.
     *
     * @param  Request  $request  The incoming HTTP request (unused)
     * @param  Event  $event  The event whose trooper roster is being managed (route model binding)
     * @return View The trooper roster management form view
     */
    public function __invoke(Request $request, Event $event): View
    {
        $this->authorize('update', $event);

        $query = new GetTroopersForEventAdminQuery($event);

        $event_shifts = $this->bus->send($query);

        $auth_trooper = $request->user();
        $allowed_org_ids = $auth_trooper->is_administrator
            ? null
            : $auth_trooper->trooper_assignments()
                ->where(TrooperAssignment::IS_MODERATOR, true)
                ->pluck(TrooperAssignment::ORGANIZATION_ID)
                ->toArray();

        foreach ($event_shifts as $shift)
        {
            foreach ($shift->event_troopers as $event_trooper)
            {
                $event_trooper->costume_options = Costume::forTrooper($event_trooper->trooper_id)
                    ->pluck('name', 'id')
                    ->toArray();

                $trooper_orgs = $event_trooper->trooper->organizations;

                $root_ids = $trooper_orgs
                    ->map(fn ($org) => (int) explode(':', $org->node_path)[0])
                    ->unique()
                    ->values()
                    ->toArray();

                $event_trooper->org_options = $trooper_orgs
                    ->whereIn('id', $root_ids)
                    ->when($allowed_org_ids !== null, fn ($c) => $c->whereIn('id', $allowed_org_ids))
                    ->values();

                if ($event_trooper->organization_id !== null)
                {
                    $org = $trooper_orgs->find($event_trooper->organization_id);
                    $event_trooper->credited_checked_ids = [
                        $org ? (int) explode(':', $org->node_path)[0] : $event_trooper->organization_id,
                    ];
                }
                else
                {
                    $event_trooper->credited_checked_ids = collect($event_trooper->costume_organization_ids ?? [])
                        ->map(function ($id) use ($trooper_orgs) {
                            $org = $trooper_orgs->find($id);

                            return $org ? (int) explode(':', $org->node_path)[0] : $id;
                        })
                        ->unique()
                        ->values()
                        ->all();
                }
            }
        }

        $data = compact('event', 'event_shifts');

        return view('pages.admin.events.troopers', $data);
    }
}
