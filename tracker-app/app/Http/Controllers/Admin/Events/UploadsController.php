<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Organization;
use App\Services\BreadCrumbService;
use App\Services\FlashMessageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the event update form.
 *
 * Provides administrators and moderators with a form to update existing event details,
 * including venue information, contact details, and organization associations.
 * Shows a draft warning if the event is not yet published.
 */
class UploadsController extends Controller
{
    /**
     * Creates a new UploadsController instance.
     *
     * @param BreadCrumbService $crumbs Service for managing breadcrumb navigation.
     */
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Events', 'admin.events.list');
    }

    /**
     * Displays the event uploads form.
     *
     * Authorizes that the user can update the event via policy check.
     * Loads all organizations for the organization selection interface.
     * Sets up breadcrumbs and renders the uploads form view.
     *
     * @param Request $request The incoming HTTP request.
     * @param Event $event The event to be updated (route model binding).
     * @return View The event uploads form view.
     */
    public function __invoke(Request $request, Event $event): View
    {
        $this->authorize('update', $event);

        $data = compact('event');

        return view('pages.admin.events.uploads', $data);
    }
}
