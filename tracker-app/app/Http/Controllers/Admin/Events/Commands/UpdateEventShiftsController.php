<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events\Commands;

use App\Http\Requests\Admin\Events\Commands\UpdateEventShiftsRequest;
use App\Messages\Events\Commands\UpdateEventShifts;
use App\Messages\Events\PageData\AdminEventPageData;
use App\Models\Event;
use Inertia\Response as InertiaResponse;

/**
 * Processes shift update form submissions.
 *
 * Handles creating new event shifts and updating existing shift start/end times.
 */
class UpdateEventShiftsController extends EventCommandController
{
    public function __invoke(UpdateEventShiftsRequest $request, Event $event): InertiaResponse
    {
        $this->authorize('update', $event);

        UpdateEventShifts::call(
            event: $event,
            shifts: $request->validated('shifts', []),
            updated_by_trooper: $request->user(),
        );

        return $this->respondWithResults(
            $request,
            AdminEventPageData::SHIFTS,
            AdminEventPageData::CHARITY,
            AdminEventPageData::ROSTER,
        );
    }
}
