<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events\Commands;

use App\Http\Requests\Admin\Events\UpdateTroopersRequest;
use App\Messages\Events\Commands\UpdateEventRoster;
use App\Messages\Events\PageData\AdminEventPageData;
use App\Models\Event;
use Inertia\Response as InertiaResponse;

class UpdateEventTroopersController extends EventCommandController
{
    public function __invoke(UpdateTroopersRequest $request, Event $event): InertiaResponse
    {
        $this->authorize('update', $event);

        UpdateEventRoster::call(
            event: $event,
            auth_trooper: $request->user(),
            validated_troopers: $request->validated('troopers', []),
            validated_guests: $request->validated('guests', []),
            allowed_org_ids: $request->user()->resolveModeratorOrgIds(),
        );

        return $this->respondWithResults($request, AdminEventPageData::ROSTER);
    }
}
