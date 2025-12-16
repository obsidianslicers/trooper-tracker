<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Displays the event creation form.
 *
 * Handles displaying the event creation form for administrators and moderators.
 * Optionally copies data from an existing event when a copyEvent parameter is provided.
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
     * Handle the request to display the event update page.
     *
     * Authorizes the user, sets up breadcrumbs, and returns the view
     * containing the form to update an existing event.
     *
     * @param Request $request The incoming HTTP request object.
     * @param Event $event The event to be updated.
     * @return View The rendered event update view.
     */
    public function __invoke(Request $request, Event $event): View|RedirectResponse
    {
        $this->authorize('create', Event::class);

        $trooper = $request->user();

        if ($request->has('copy_id'))
        {
            $event = $this->copyEvent($request, $trooper);

            return redirect()->route('admin.events.update', compact('event'));
        }

        $event = new Event();

        $this->assignOrganization($request, $event, $trooper);

        $data = compact('event');

        return view('pages.admin.events.create', $data);
    }

    private function copyEvent(Request $request, Trooper $trooper): Event
    {
        $copy_id = $request->query('copy_id');

        $event = Event::moderatedBy($trooper)->findOrFail($copy_id);

        $event_copy = $event->replicate();
        $event_copy->name = 'Copy of ' . $event->name;
        $event_copy->status = EventStatus::DRAFT;
        $event_copy->push();

        foreach ($event->event_shifts as $shift)
        {
            $shift_copy = $shift->replicate();
            $shift_copy->event_id = $event_copy->id;
            $shift_copy->status = EventStatus::OPEN;
            $shift_copy->save();
        }

        foreach ($event->event_organizations as $organization)
        {
            $organization_copy = $organization->replicate();
            $organization_copy->event_id = $event_copy->id;
            $organization_copy->save();
        }

        return $event_copy;
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
