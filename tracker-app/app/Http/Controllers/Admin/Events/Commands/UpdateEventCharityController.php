<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events\Commands;

use App\Http\Requests\Admin\Events\Commands\UpdateEventCharityRequest;
use App\Messages\Events\Commands\UpdateEventCharity;
use App\Messages\Events\PageData\AdminEventPageData;
use App\Models\Event;
use Inertia\Response as InertiaResponse;

class UpdateEventCharityController extends EventCommandController
{
    public function __invoke(UpdateEventCharityRequest $request, Event $event): InertiaResponse
    {
        $this->authorize('update', $event);

        UpdateEventCharity::call(event: $event, shifts: $request->validated('shifts', []));

        return $this->respondWithResults($request, AdminEventPageData::CHARITY);
    }
}
