<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events\Commands;

use App\Enums\FlashType;
use App\Http\Requests\Admin\Events\Commands\UpdateEventRequest;
use App\Messages\Events\Commands\UpdateEvent;
use App\Models\Event;
use Inertia\Response as InertiaResponse;

/**
 * Processes event update form submissions.
 *
 * Handles updating existing event details including venue information, contact details,
 * event dates, amenities, and organization associations. Updates both the event record
 * and its related EventOrganization pivot records for access control.
 */
class UpdateEventController extends EventCommandController
{
    public function __invoke(UpdateEventRequest $request, Event $event): InertiaResponse
    {
        UpdateEvent::call(
            event: $event,
            data: $request->validated(),
            updated_by_trooper: $request->user(),
        );

        FlashType::updated($event);

        return $this->respondWithResults($request);
    }
}
