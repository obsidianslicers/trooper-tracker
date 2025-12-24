<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
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
class CopyController extends Controller
{
    /**
     * Creates a new UpdateController instance.
     *
     * @param BreadCrumbService $crumbs Service for managing breadcrumb navigation.
     */
    public function __construct(
        private readonly BreadCrumbService $crumbs,
        private readonly FlashMessageService $flash)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Events', 'admin.events.list');
    }

    /**
     * Displays the event update form.
     *
     * Authorizes that the user can update the event via policy check.
     * Sets up breadcrumbs and renders the copy form view.
     *
     * @param Request $request The incoming HTTP request.
     * @param Event $event The event to be copied (route model binding).
     * @return View The event copy form view.
     */
    public function __invoke(Request $request, Event $event): View
    {
        $this->authorize('update', $event);

        $event->name = 'COPY OF ' . $event->name;

        $data = compact('event');

        return view('pages.admin.events.copy', $data);
    }
}
