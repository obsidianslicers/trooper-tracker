<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\EventTypes\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventTypes\Commands\StoreEventTypeRequest;
use App\Messages\EventTypes\Commands\CreateEventType;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class StoreEventTypeController extends Controller
{
    public function __invoke(StoreEventTypeRequest $request): InertiaResponse|SymfonyResponse
    {
        $event_type = CreateEventType::call(
            name: $request->validated('name'),
            description: $request->validated('description'),
            is_counted: $request->boolean('is_counted'),
            is_costume_required: $request->boolean('is_costume_required'),
            is_active: $request->boolean('is_active'),
        );

        FlashType::created($event_type);

        return Inertia::location(route('admin.event-types.index'));
    }
}
