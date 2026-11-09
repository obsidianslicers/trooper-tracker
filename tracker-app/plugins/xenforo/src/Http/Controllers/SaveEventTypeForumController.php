<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Http\Controllers;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Models\EventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use TroopTracker\Plugins\Xenforo\Models\EventTypeForum;

/**
 * Saves the forums an event type's threads open in and are archived to.
 */
class SaveEventTypeForumController extends Controller
{
    public function __invoke(Request $request, EventType $event_type): RedirectResponse
    {
        $this->authorize('update', $event_type);

        $validated = $request->validate([
            'xenforo_event_forum_id' => ['nullable', 'integer', 'min:1'],
            'xenforo_archive_forum_id' => ['nullable', 'integer', 'min:1'],
        ]);

        EventTypeForum::store($event_type->id, [
            EventTypeForum::EVENT_FORUM_ID => $validated['xenforo_event_forum_id'] ?? null,
            EventTypeForum::ARCHIVE_FORUM_ID => $validated['xenforo_archive_forum_id'] ?? null,
        ]);

        FlashType::success("Forums for \"{$event_type->name}\" saved.");

        return redirect()->route('admin.event-types.update', compact('event_type'));
    }
}
