<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class ListController
 *
 * Handles the display of the main events list in the admin section.
 * This controller fetches and displays a paginated list of events, which can be
 * filtered by status (active, past, future) and by organization. It ensures that
 * non-administrator users can only see events for organizations they moderate.
 * @package App\Http\Controllers\Admin\Events
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
     * Sets up breadcrumbs and retrieves a paginated list of events.
     * The list is filtered to show only active events. If an 'organization_id'
     * is provided in the request, the list is further filtered to that organization.
     * Non-administrator users will only see events for organizations they moderate.
     *
     * @param Request $request The incoming HTTP request object.
     * @return View The rendered view for the events list.
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $organization = $this->getOrganization($request);

        $events = $this->getEvents($request, $trooper, $organization);

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
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
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
     * The query is built based on the requested status (active, past, future), an
     * optional organization filter, and the user's authorization level (admin vs. moderator).
     *
     * @param Request $request The incoming HTTP request.
     * @param Trooper $trooper The authenticated trooper.
     * @param Organization|null $organization The organization to filter by, if any.
     * @return LengthAwarePaginator The paginated list of events.
     */
    private function getEvents(Request $request, Trooper $trooper, ?Organization $organization): LengthAwarePaginator
    {
        $q = Event::with([
            'organization.trooper_assignments' => function ($q) use ($trooper)
            {
                $q->where(TrooperAssignment::TROOPER_ID, $trooper->id)
                    ->where(TrooperAssignment::MODERATOR, true);
            }
        ]);

        if ($request->has('status'))
        {
            $status = EventStatus::from($request->query('status'));

            $q = $q->where(Event::STATUS, $status);
        }

        if ($organization != null)
        {
            $organization_id = $request->query('organization_id');

            $q = $q->where(Event::ORGANIZATION_ID, $organization_id);
        }

        if (!$trooper->isAdministrator())
        {
            $q = $q->moderatedBy($trooper);
        }

        if ($request->has('search_term') && strlen($request->query('search_term', '')) >= 3)
        {
            $q = $q->searchFor($request->query('search_term'));
        }

        $q->orderByDesc(Event::ENDS_AT);

        return $q->paginate(15);
    }
}
