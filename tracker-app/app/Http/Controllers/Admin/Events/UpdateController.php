<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the event update form.
 *
 * Provides administrators and moderators with a form to update existing event details,
 * including venue information, contact details, and organization associations.
 * Shows a draft warning if the event is not yet published.
 */
class UpdateController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Events', 'admin.events.list');
    }

    /**
     * Displays the event update form
     *
     * Authorizes that the user can update the event via policy check.
     * Loads all organizations for the organization selection interface.
     * Sets up breadcrumbs and renders the update form view.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Event  $event  The event to be updated (route model binding)
     * @return View The event update form view
     */
    public function __invoke(Request $request, Event $event): View
    {
        $this->authorize('update', $event);

        if ($event->is_draft)
        {
            $msg = 'This event is currently in Draft status and not visible to troopers.';

            $this->flash->warning($msg);
        }

        $organizations = Organization::ofTypeOrganizations()->orderBy(Organization::NAME)->get();

        foreach ($organizations as $organization)
        {
            $event_organization = $event->organizations
                ->filter(fn($eo) => $eo->id == $organization->id)
                ->filter(fn($eo) => $eo->pivot->can_attend)
                ->first();

            $organization->pivot = $event_organization->pivot ?? null;
        }

        $data = compact('event', 'organizations');

        return view('pages.admin.events.update', $data);
    }
}
