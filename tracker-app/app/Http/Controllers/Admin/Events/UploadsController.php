<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the event uploads management page.
 *
 * Provides administrators and moderators with an interface to manage event photo
 * uploads, including viewing existing uploads and uploading new administrative images.
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
     * Displays the event uploads management page.
     *
     * Authorizes that the user can update the event via policy check.
     * Sets up breadcrumbs and renders the uploads management view where
     * administrators can view and manage event photos.
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
