<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Organization;
use App\Models\Trooper;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Handles the display of the authenticated user's notification settings page.
 */
class UpdateOrganizationsController extends Controller
{
    /**
     * UpdateController constructor.
     *
     * @param BreadCrumbService $crumbs The service for managing breadcrumbs.
     */
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Events', 'admin.events.list');
    }

    /**
     * Handle the incoming request to display the notification settings.
     *
     * This method retrieves the notification preferences and organizational
     * notification subscriptions for the currently authenticated user and
     * renders the corresponding view.
     *
     * @param Request $request The incoming HTTP request.
     * @return View The rendered notification settings view.
     */
    public function __invoke(Request $request, Event $event): View
    {
        $event_organizations = $this->getEventOrganizations($event, $request->user());

        $data = [
            'event' => $event,
            'event_organizations' => $event_organizations
        ];

        return view('pages.admin.events.organizations', $data);
    }

    /**
     * Gathers all notification-related data for a given trooper.
     *
     * This method fetches all organizations and cross-references them with the
     * trooper's notification assignments to determine which organizational
     * notifications are enabled. It also includes the trooper's global
     * notification preferences.
     *
     * @param Trooper $trooper The trooper for whom to fetch notification data.
     * @return array An array of data ready for the view.
     */
    private function getEventOrganizations(Event $event, Trooper $trooper): Collection
    {
        $with = [
            'event_organizations' => function ($q) use ($event)
            {
                $q->where(EventOrganization::EVENT_ID, $event->id);
            }
        ];

        $event_organizations = Organization::with($with)->orderBy(Organization::SEQUENCE)->get();

        return $event_organizations;
    }
}
