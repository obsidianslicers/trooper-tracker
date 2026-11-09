<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Http\Controllers\PageController;
use App\Messages\Organizations\Queries\GetOrganizationTree;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventType;
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
class CreateController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Events', 'admin.events.list');
    }

    /**
     * Displays the event creation form with a new event instance
     *
     * Authorizes the user can create events, initializes a new Event model
     * with default values (the first event type on the list, DRAFT status), and optionally assigns
     * an organization from query parameters. Returns the view with the event,
     * organization hierarchy, and available organizations.
     *
     * @param  Request  $request  The incoming HTTP request object
     * @param  Event  $event  Event model (injected but not used, new instance created)
     * @return View The rendered event creation view
     */
    public function __invoke(Request $request, Event $event): View
    {
        $this->authorize('create', Event::class);

        $trooper = $request->user();

        $organization_hierarchy = GetOrganizationTree::call()->map(fn (array $org) => (object) $org);

        $event = new Event;

        // Set defaults
        $event->type = EventType::query()->active()->ordered()->value(EventType::SLUG);
        $event->status = EventStatus::DRAFT;
        $event->require_mission_brief_ack = false;

        // The form has always opened with these on "Yes" (the browser picked the first option
        // while Alpine blanked the select), so keep that now that the form shows these values.
        $event->tentative_signups_allowed = true;
        $event->secure_staging_area = true;
        $event->allow_blasters = true;
        $event->allow_props = true;
        $event->parking_available = true;
        $event->accessible = true;

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

        //  An unchecked box isn't submitted, so after a failed submit a missing value means "no".
        $resubmitted = !empty(old());

        foreach ($organizations as $organization)
        {
            $default = !$resubmitted && $organization->can_attend_default ? 'true' : 'false';

            $organization->pivot = new EventOrganization;

            $organization->pivot->can_attend = filter_var(old("organizations.{$organization->id}.can_attend", $default), FILTER_VALIDATE_BOOLEAN);
            $organization->pivot->troopers_allowed = old("organizations.{$organization->id}.troopers_allowed");
            $organization->pivot->handlers_allowed = old("organizations.{$organization->id}.handlers_allowed");
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
            $q = Organization::eventHostableBy($trooper);

            $event->organization = $q->findOrFail($event->organization_id);
        }
    }
}
