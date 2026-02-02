<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Features\Organizations\Queries\GetOrganizationHierarchyQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Displays the event creation form.
 *
 * Handles displaying the event creation form for administrators and moderators.
 * Initializes a new event with default values (REGULAR type, DRAFT status) and
 * optionally assigns an organization based on query parameters. Provides the
 * organization hierarchy for form selection.
 */
class CreateController extends MagicBusController
{
    protected function initialized()
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Events', 'admin.events.list');
    }

    /**
     * Displays the event creation form with a new event instance.
     *
     * Authorizes the user can create events, initializes a new Event model
     * with default values (REGULAR type, DRAFT status), and optionally assigns
     * an organization from query parameters. Returns the view with the event,
     * organization hierarchy, and available organizations.
     *
     * @param  Request  $request  The incoming HTTP request object.
     * @param  Event  $event  Event model (injected but not used, new instance created).
     * @return View The rendered event creation view.
     */
    public function __invoke(Request $request, Event $event): View
    {
        $this->authorize('create', Event::class);

        $trooper = $request->user();

        $organization_hierarchy_query = new GetOrganizationHierarchyQuery;

        $organization_hierarchy = $this->bus->send($organization_hierarchy_query)->map(fn (array $org) => (object) $org);

        $event = new Event;

        // Set defaults
        $event->type = EventType::REGULAR;
        $event->status = EventStatus::DRAFT;

        // Fill with old input if available
        if (!empty(old()))
        {
            $event->fill(old());
        }

        $this->assignOrganization($request, $event, $trooper);

        $organizations = $this->getOrganizations();

        $mode = old('mode', 'email');

        $data = compact('event', 'organization_hierarchy', 'organizations', 'mode');

        return view('pages.admin.events.create', $data);
    }

    private function getOrganizations(): Collection
    {
        $organizations = Organization::ofTypeOrganizations()->orderBy(Organization::NAME)->get();

        foreach ($organizations as $organization)
        {
            $organization->can_attend = old("organizations.{$organization->id}.can_attend", 'true');
            $organization->troopers_allowed = old("organizations.{$organization->id}.troopers_allowed");
            $organization->handlers_allowed = old("organizations.{$organization->id}.handlers_allowed");
        }

        return $organizations;
    }

    private function assignOrganization(Request $request, Event $event, Trooper $trooper)
    {
        if ($request->has('organization_id'))
        {
            $event->organization_id = $request->query('organization_id');
        }
        else
        {
            $event->organization_id = old('organization_id', $event->organization_id.'');
        }

        if ($event->organization_id != null)
        {
            $q = Organization::moderatedBy($trooper);

            $event->organization = $q->findOrFail($event->organization_id);
        }
    }
}
