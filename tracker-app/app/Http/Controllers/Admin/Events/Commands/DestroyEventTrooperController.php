<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events\Commands;

use App\Enums\FlashType;
use App\Messages\Events\Commands\RemoveEventTrooper;
use App\Messages\Events\PageData\AdminEventPageData;
use App\Models\Event;
use App\Models\EventTrooper;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

class DestroyEventTrooperController extends EventCommandController
{
    public function __invoke(Request $request, Event $event, EventTrooper $event_trooper): InertiaResponse
    {
        $this->authorize('update', $event);

        abort_if($event_trooper->event_shift->event_id !== $event->id, 404);

        $trooper_name = $event_trooper->trooper->display_name;

        RemoveEventTrooper::call(event_trooper: $event_trooper);

        FlashType::success(__('admin.:name was removed from the roster.', ['name' => $trooper_name]));

        return $this->respondWithResults($request, AdminEventPageData::ROSTER);
    }
}
