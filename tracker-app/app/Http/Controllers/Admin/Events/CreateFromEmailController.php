<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

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
class CreateFromEmailController extends Controller
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

        $event = new Event();

        $this->assignOrganization($request, $event, $trooper);

        $data = compact('event');

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
