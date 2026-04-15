<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Facades\TroopTrackerFacade;
use App\Features\Events\Queries\GetEventDisplayQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use App\Services\Forums\XenforoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the event sign-up page with all shifts and current trooper assignments.
 *
 * This controller renders the event sign-up interface where troopers can view
 * available shifts, see who is already signed up, and register for shifts.
 * It loads the event with all related data including organizations, shifts,
 * and trooper assignments.
 */
class EventDisplayController extends MagicBusController
{
    /**
     * Initialize controller by setting up breadcrumb navigation.
     */
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Events', 'events.list');
    }

    /**
     * Handle the incoming request to display the event sign-up page
     *
     * Loads the event with all shifts, trooper assignments, and organization
     * details. Each shift is enriched with its parent event reference for
     * easy access in the view.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Event  $event  The event to display for sign-up
     * @return View The rendered event sign-up page
     */
    public function __invoke(Request $request, Event $event, XenforoService $xenforo): View
    {
        $trooper = $request->user();

        $event_query = new GetEventDisplayQuery($event, $trooper);

        $event = $this->bus->send($event_query);

        $can_moderate = $trooper->isModeratorForOrganization($event->organization);

        $mission_brief_required = (bool) $event->require_mission_brief_ack;
        $has_mission_brief_ack = $mission_brief_required
            ? $event->hasMissionBriefAcknowledgementFor($trooper)
            : false;

        // Header background color
        $bg = $event->at_risk ? 'bg-danger' : 'bg-primary';
        if ($event->is_locked)
        {
            $bg = 'bg-secondary';
        }

        // XenForo base URL, if configured
        $xenforoBaseUrl = config('services.xenforo.base_url', '');

        // XenForo thread posts (optional)
        $xenforoThreadPosts = [];
        if (TroopTrackerFacade::isXenforoIntegrationConfigured() && !empty($event->thread_id) && !empty($event->post_id))
        {
            $threadId = (int) $event->thread_id;
            $excludePostId = (int) $event->post_id;
            $xenforoThreadPosts = $xenforo->get_thread_posts($threadId, exclude_post_id: $excludePostId, per_page: 50, max_pages: 20);
        }

        $data = compact(
            'event',
            'can_moderate',
            'bg',
            'xenforoBaseUrl',
            'xenforoThreadPosts',
            'mission_brief_required',
            'has_mission_brief_ack'
        );

        return view('pages.events.event-display', $data);
    }
}
