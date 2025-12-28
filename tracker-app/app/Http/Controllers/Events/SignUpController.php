<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Displays the event sign-up page with all shifts and current trooper assignments.
 *
 * This controller renders the event sign-up interface where troopers can view
 * available shifts, see who is already signed up, and register for shifts.
 * It loads the event with all related data including organizations, shifts,
 * and trooper assignments.
 */
class SignUpController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param BreadCrumbService $crumbs The breadcrumb service for navigation
     */
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Events', 'events.list');
    }

    /**
     * Handle the incoming request to display the event sign-up page.
     *
     * Loads the event with all shifts, trooper assignments, and organization
     * details. Each shift is enriched with its parent event reference for
     * easy access in the view.
     *
     * @param Request $request The incoming request
     * @param Event $event The event to display for sign-up
     * @return View The rendered event sign-up page
     */
    public function __invoke(Request $request, Event $event): View
    {
        $with = [
            'organization',
            'organizations.organization',
            'organizations' => function ($query)
            {
                $query->orderBy(Organization::NAME);
            },
            'event_shifts.event_troopers.trooper',
            'event_shifts.event_troopers.added_by_trooper',
            'event_shifts.event_troopers.organization_costume.organization',
            'event_shifts.event_troopers' => function ($query)
            {
                $query->orderBy(EventTrooper::SIGNED_UP_AT);
            },
        ];

        $event = Event::with($with)
            ->withShifts()
            ->findOrFail($event->id);

        //  re-link shifts to event for view access (see SignUpHtmxController)
        foreach ($event->event_shifts as $event_shift)
        {
            $event_shift->event = $event;

            foreach ($event_shift->event_troopers as $event_trooper)
            {
                $event_trooper->event_shift = $event_shift;

                if ($event_trooper->canUpdateCostume($event_shift, Auth::user()))
                {
                    //  performance optimization: load costumes only if the trooper can update
                    $event_trooper->costumes = $event_trooper->getCostumes();
                }
            }
        }

        $data = compact('event');

        return view('pages.events.signup', $data);
    }
}
