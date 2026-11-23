<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events\Pages;

use App\Http\Controllers\Controller;
use App\Messages\Events\PageData\Admin\EventTrooperOrganizationsPageData;
use App\Models\Event;
use App\Models\EventTrooper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The clubs a roster row can credit for a chosen costume, and which of them to tick.
 */
class ListEventTrooperOrganizationsController extends Controller
{
    public function __invoke(Request $request, Event $event, EventTrooper $event_trooper): JsonResponse
    {
        $this->authorize('update', $event);
        abort_if($event_trooper->event_shift->event_id !== $event->id, 404);

        $costume_id = $request->input('costume_id') !== null && $request->input('costume_id') !== ''
            ? (int) $request->input('costume_id')
            : null;

        return EventTrooperOrganizationsPageData::call(
            actor: $request->user(),
            event_trooper: $event_trooper,
            costume_id: $costume_id,
        )->response($request);
    }
}
