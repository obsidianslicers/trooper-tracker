<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Features\Events\Queries\GetEventsForModeratorQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Filters\EventFilter;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays a paginated and filterable list of events for administrators and moderators.
 *
 * This controller follows the **Action-Domain-Responder (ADR)** pattern:
 * - **Action (Controller):** Orchestrates the request handling and view rendering
 * - **Domain (Query/Handler):** GetEventsForModeratorQuery retrieves events through MagicBus
 * - **Responder:** Blade view renders the event list table
 *
 * Key features:
 * - Administrators see all events across all organizations
 * - Moderators see only events from organizations they moderate
 * - Supports filtering by status, organization, and search term
 * - Provides pagination for large result sets
 * - Includes breadcrumb navigation
 *
 * Query parameters:
 * - status: Filter by EventStatus enum value (open, closed, cancelled, etc.)
 * - organization_id: Filter by specific organization
 * - search_term: Search events by name
 * - page: Pagination page number
 */
class ListController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    /**
     * Handle the incoming request to display the event list
     *
     * Orchestrates the event retrieval workflow:
     * 1. Authenticates the trooper (via middleware)
     * 2. Retrieves organization filter if provided
     * 3. Dispatches GetEventsForModeratorQuery via MagicBus for filtered, paginated results
     * 4. Prepares view data with events, filters, and status options
     *
     * @param  Request  $request  The incoming HTTP request with optional filter parameters
     * @param  EventFilter  $filter  The filter service for applying query constraints
     * @return View The event list view with filtered and paginated results
     */
    public function __invoke(Request $request, EventFilter $filter): View
    {
        $trooper = $request->user();

        $organization = $this->getOrganization($request);

        $get_events_query = new GetEventsForModeratorQuery($filter, $trooper);

        $events = $this->bus->send($get_events_query);

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
     * @param  Request  $request  The incoming HTTP request.
     * @return Organization|null The found Organization or null if no ID is provided.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException if an `organization_id` is provided but not found.
     */
    private function getOrganization(Request $request): ?Organization
    {
        if ($request->has('organization_id') && $request->query('organization_id') > 0)
        {
            $organization_id = $request->query('organization_id');

            return Organization::findOrFail($organization_id);
        }

        return null;
    }
}
