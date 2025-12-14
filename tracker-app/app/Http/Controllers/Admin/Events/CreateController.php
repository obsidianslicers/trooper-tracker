<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class UpdateController
 *
 * Handles displaying the form to update an existing event.
 * @package App\Http\Controllers\Admin\Events
 */
class CreateController extends Controller
{
    /**
     * CreateController constructor.
     *
     * @param BreadCrumbService $crumbs The service for managing breadcrumbs.
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
    public function __invoke(Request $request, Event $event): View
    {
        $this->authorize('create', Event::class);

        $trooper = $request->user();

        $event = $this->getEvent($request, $trooper);

        $this->assignOrganization($request, $event, $trooper);

        $data = compact('event');

        return view('pages.admin.events.create', $data);
    }

    private function getEvent(Request $request, Trooper $trooper): Event
    {
        $event = new Event();

        // if ($request->has('copy_id'))
        // {
        //     $copy_id = $request->query('copy_id');

        //     $copy = Event::moderatedBy($trooper)->findOrFail($copy_id);

        //     $event->organization_id = $copy->organization_id;
        //     $event->name = 'Copy of ' . $copy->name;
        // }

        return $event;
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
