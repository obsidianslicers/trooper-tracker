<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\EventTypes\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventTypes\UpdateRequest;
use App\Messages\EventTypes\Commands\UpdateEventType;
use App\Models\EventType;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class UpdateSubmitController extends Controller
{
    public function __invoke(UpdateRequest $request, EventType $event_type): InertiaResponse|SymfonyResponse
    {
        $event_type = UpdateEventType::call(
            event_type: $event_type,
            name: $request->validated('name'),
            description: $request->validated('description'),
            is_counted: $request->boolean('is_counted'),
            is_active: $request->boolean('is_active'),
        );

        FlashType::updated($event_type);

        return Inertia::location(route('admin.event-types.index'));
    }
}
