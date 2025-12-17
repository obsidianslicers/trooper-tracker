<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Filters\EventFilter;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays a paginated and filterable list of events.
 *
 * Provides administrators and moderators with a list of all events,
 * with filtering capabilities for status, organization, and search terms.
 * Supports pagination for large result sets.
 */
class ListController extends Controller
{
    /**
     * Creates a new ListController instance.
     *
     * @param BreadCrumbService $crumbs Service for managing breadcrumb navigation.
     */
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    /**
     * Displays the filtered and paginated event list.
     *
     * Authorizes that the user can view events, applies filters from query
     * parameters (status, organization_id, q for search), and paginates results.
     * Sets up breadcrumb navigation and renders the event list view.
     *
     * @param Request $request The incoming HTTP request with optional filter parameters.
     * @param EventFilter $filter The filter service for applying query constraints.
     * @return View The event list view with filtered and paginated results.
     */
    public function __invoke(Request $request, EventFilter $filter): View
    {
        $organization = $this->getOrganization($request);

        $events = $this->getEvents($request, $filter);

        $status_options = EventStatus::toArray();

        $data = [
            'events' => $events,
            'organization' => $organization,
            'status' => $request->query('status', null),
            'search_term' => $request->query('search_term'),
            'status_options' => $status_options,
        ];

        return view('pages.admin.events.list', $data);
    }

    /**
     * Retrieves the organization from the request if an 'organization_id' is provided.
     *
     * @param Request $request The incoming HTTP request.
     * @return Organization|null The found Organization or null if no ID is provided.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException if an `organization_id` is provided but not found.
     */
    private function getOrganization(Request $request): ?Organization
    {
        if ($request->has('organization_id'))
        {
            $organization_id = $request->query('organization_id');

            return Organization::findOrFail($organization_id);
        }

        return null;
    }

    /**
     * Builds and executes the query to retrieve a paginated list of events.
     *
     * The query is built based on the requested status, an optional organization filter,
     * a search term, and the user's authorization level (admin vs. moderator).
     * @param Request $request The incoming HTTP request.
     * @param Trooper $trooper The authenticated trooper.
     * @param Organization|null $organization The organization to filter by, if any.
     * @return LengthAwarePaginator The paginated list of events.
     */
    private function getEvents(Request $request, EventFilter $filter): LengthAwarePaginator
    {
        $trooper = $request->user();

        $q = Event::with([
            'organization.trooper_assignments' => function ($q) use ($trooper)
            {
                $q->where(TrooperAssignment::TROOPER_ID, $trooper->id)
                    ->where(TrooperAssignment::IS_MODERATOR, true);
            }
        ]);

        $q = $q->withCount('event_shifts');

        $q = $q->filterWith($filter)->moderatedBy($trooper);

        $q->orderByDesc(Event::EVENT_END);

        return $q->paginate(15)->withQueryString();
    }
}
