<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

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
 * Handles displaying a paginated and filterable list of events in the admin section.
 *
 * Supports filtering by status, organization, and search term, while respecting user permissions.
 */
class ListController extends Controller
{
    /**
     * ListController constructor.
     *
     * @param BreadCrumbService $crumbs The service for managing breadcrumbs.
     */
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    /**
     * Handle the request to display the events list page.
     * 
     * Fetches and displays a paginated list of events based on request filters
     * such as status, organization, and search term.
     * @param Request $request The incoming HTTP request object.
     * @return View The rendered view for the events list.
     */
    public function __invoke(Request $request, EventFilter $filter): View
    {
        $organization = $this->getOrganization($request);

        $events = $this->getEvents($request, $filter);

        $data = [
            'events' => $events,
            'organization' => $organization,
            'status' => $request->query('status', null),
            'search_term' => $request->query('search_term')
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
                    ->where(TrooperAssignment::MODERATOR, true);
            }
        ]);

        $q = $q->filterWith($filter);

        if (!$trooper->isAdministrator())
        {
            $q = $q->moderatedBy($trooper);
        }

        $q->orderByDesc(Event::ENDS_AT);

        return $q->paginate(15)->withQueryString();
    }
}
