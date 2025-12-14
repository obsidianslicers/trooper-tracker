<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Events\CreateRequest;
use App\Models\Base\EventShift;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Organization;
use App\Services\FlashMessageService;
use Illuminate\Http\RedirectResponse;

/**
 * Class CreateSubmitController
 *
 * Handles the submission of the form for updating an existing event.
 * @package App\Http\Controllers\Admin\Events
 */
class CreateSubmitController extends Controller
{
    /**
     * CreateSubmitController constructor.
     *
     * @param FlashMessageService $flash The service for displaying flash messages.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Handle the incoming request to update a event.
     *
     * Validates the request, updates the event's properties, saves it,
     * and then redirects with a success message.
     *
     * @param CreateRequest $request The validated request containing the updated data.
     * @param Event $event The event to be updated.
     * @return RedirectResponse A redirect response to the events list.
     */
    public function __invoke(CreateRequest $request): RedirectResponse
    {
        $event = Event::fromEmail($request->validated('source'));
        $event->organization_id = $request->validated('organization_id');
        $event->save();

        $event_organization = new EventOrganization();
        $event_organization->event_id = $event->id;
        $event_organization->organization_id = $this->getOrganizationId($event->organization_id);
        $event_organization->can_attend = true;
        $event_organization->save();

        $event_shift = new EventShift();
        $event_shift->event_id = $event->id;
        $event_shift->shift_starts_at = $event->event_start;
        $event_shift->shift_ends_at = $event->event_end;
        $event_shift->save();

        $this->flash->created($event);

        return redirect()->route('admin.events.update', compact('event'));
    }

    private function getOrganizationId(int $organization_id): int
    {
        $organization = Organization::findOrFail($organization_id);

        while ($organization->parent_id !== null)
        {
            $organization = Organization::findOrFail($organization->parent_id);
        }

        return $organization->id;
    }
}

