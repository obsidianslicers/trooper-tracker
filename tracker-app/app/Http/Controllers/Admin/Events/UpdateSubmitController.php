<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Events\UpdateRequest;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Services\FlashMessageService;
use Illuminate\Http\RedirectResponse;

/**
 * Class UpdateSubmitController
 *
 * Handles the submission of the form for updating an existing event.
 * @package App\Http\Controllers\Admin\Events
 */
class UpdateSubmitController extends Controller
{
    /**
     * UpdateSubmitController constructor.
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
     * @param UpdateRequest $request The validated request containing the updated data.
     * @param Event $event The event to be updated.
     * @return RedirectResponse A redirect response to the events list.
     */
    public function __invoke(UpdateRequest $request, Event $event): RedirectResponse
    {
        $reset_limits = $event->limit_organizations != $request->validated('limit_organizations');

        $event->name = $request->validated('name');
        $event->starts_at = $request->validated('starts_at');
        $event->ends_at = $request->validated('ends_at');
        $event->status = $request->validated('status');
        $event->limit_organizations = $request->validated('limit_organizations');

        $event->save();

        if ($reset_limits)
        {
            $this->resetLimits($event);
        }

        $this->flash->updated($event);

        return redirect()->route('admin.events.update', ['event' => $event]);
    }

    private function resetLimits(Event $event)
    {
        if ($event->limit_organizations)
        {
            $can_update = $event->organizations()->where(EventOrganization::ORGANIZATION_ID, $event->organization_id)->exists();

            if ($can_update)
            {
                // Update if it already exists
                $event->organizations()->updateExistingPivot($event->organization_id,
                    [
                        EventOrganization::CAN_ATTEND => true,
                        EventOrganization::TROOPERS_ALLOWED => null,
                        EventOrganization::HANDLERS_ALLOWED => null,
                    ]);
            }
            else
            {
                // Otherwise create
                $event->organizations()->attach($event->organization_id,
                    [
                        EventOrganization::CAN_ATTEND => true,
                        EventOrganization::TROOPERS_ALLOWED => null,
                        EventOrganization::HANDLERS_ALLOWED => null,
                    ]);
            }
        }
        else
        {
            $event->organizations()->update([
                EventOrganization::CAN_ATTEND => false,
                EventOrganization::TROOPERS_ALLOWED => null,
                EventOrganization::HANDLERS_ALLOWED => null,
            ]);
        }
    }
}

