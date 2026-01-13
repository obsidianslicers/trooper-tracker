<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Services\BreadCrumbService;
use App\Services\Organizations\GetOrganizationHierarchyQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Displays the event creation form.
 *
 * Handles displaying the event creation form for administrators and moderators.
 * Initializes a new event with default values (REGULAR type, DRAFT status) and
 * optionally assigns an organization based on query parameters. Provides the
 * organization hierarchy for form selection.
 */
class CreateController extends Controller
{
    /**
     * Creates a new CreateController instance.
     *
     * @param BreadCrumbService $crumbs Service for managing breadcrumb navigation.
     */
    public function __construct(private readonly BreadCrumbService $crumbs)
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
     * @param Request $request The incoming HTTP request object.
     * @param Event $event Event model (injected but not used, new instance created).
     * @param GetOrganizationHierarchyQuery $get_organization_hierarchy Query for organization hierarchy.
     * @return View|RedirectResponse The rendered event creation view.
     */
    public function __invoke(
        Request $request,
        Event $event,
        GetOrganizationHierarchyQuery $get_organization_hierarchy): View|RedirectResponse
    {
        $this->authorize('create', Event::class);

        $trooper = $request->user();

        $organization_hierarchy = $get_organization_hierarchy()->map(fn(array $org) => (object) $org);

        $event = new Event();

        if (empty(old()))
        {
            $event->type = EventType::REGULAR;
            $event->status = EventStatus::DRAFT;
        }
        else
        {
            $event->fill(old());
        }

        $this->assignOrganization($request, $event, $trooper);

        $organizations = Organization::ofTypeOrganizations()->orderBy(Organization::NAME)->get();

        $mode = old('mode', 'email');

        $data = compact('event', 'organization_hierarchy', 'organizations', 'mode');

        return view('pages.admin.events.create', $data);
    }

    private function assignOrganization(Request $request, Event $event, Trooper $trooper)
    {
        if ($request->has('organization_id'))
        {
            $event->organization_id = $request->query('organization_id');
        }

        if ($event->organization_id != null)
        {
            $q = Organization::moderatedBy($trooper);

            $event->organization = $q->findOrFail($event->organization_id);
        }
    }
}
