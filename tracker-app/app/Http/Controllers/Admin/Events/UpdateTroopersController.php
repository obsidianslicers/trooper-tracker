<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use App\Models\Organization;
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
     * Authorizes that the user can update the event via policy check.
     * Loads event shifts with roster scope and renders the trooper
     * management view.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Event  $event  The event whose trooper roster is being managed (route model binding)
     * @return View The trooper roster management form view
     */
    public function __invoke(Request $request, Event $event): View
    {
        $this->authorize('update', $event);

        $event_shifts = $event->event_shifts()->roster()->get();

        $orgs = Organization::ofTypeOrganizations()->pluck('name', 'id');

        $event_shifts->each(function ($shift) use ($orgs)
        {
            $shift->event_troopers->transform(function ($et) use ($orgs)
            {
                // 1. Get the list of IDs this specific costume *could* represent (from your JSON/Column)
                $potential_orgs = collect($et->costume_organization_ids ?? []);

                // 2. Get the IDs the trooper is *actually* approved for (filtered by this costume)
                // Note: We check the trooper_costumes bridge for an organization_costume that matches the current costume_id
                $approved_orgs = $et->trooper->trooper_costumes
                    ->filter(function ($tc) use ($et)
                    {
                        // Reaching through to the organization_costume to check the physical kit ID
                        return optional($tc->organization_costume)->costume_id == $et->costume_id;
                    })
                    ->pluck('organization_costume.organization_id') // Pull the Org ID from the bridge
                    ->unique();

                // 3. The Intersection: Only organizations that are BOTH potential and approved
                $final_orgs = $potential_orgs->intersect($approved_orgs);

                $names = $final_orgs->map(fn($id) => $orgs[$id] ?? '??');

                // 4. Generate the legacy (DUAL) string based on the verified intersection
                $many = $names->count() > 1;
                $prefix = $many ? '(*) ' : '';
                $name_list = $names->implode(', ');

                $et->display_clubs = "{$prefix}{$name_list}";

                return $et;
            });
        });

        $data = compact('event', 'event_shifts');

        return view('pages.admin.events.troopers', $data);
    }
}
