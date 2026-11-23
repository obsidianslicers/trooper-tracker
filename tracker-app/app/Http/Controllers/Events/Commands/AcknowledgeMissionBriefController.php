<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Http\Controllers\PageController;
use App\Messages\Events\Commands\AcknowledgeEventMissionBrief;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Records a trooper's acknowledgement of an event mission brief.
 */
class AcknowledgeMissionBriefController extends PageController
{
    public function __invoke(Request $request, Event $event): RedirectResponse
    {
        $trooper = $request->user();

        // Only authenticated troopers can acknowledge mission briefs
        if ($trooper === null)
        {
            return redirect()->route('auth.login');
        }

        AcknowledgeEventMissionBrief::call(event: $event, trooper: $trooper);

        return redirect()->route('events.display', compact('event'))
            ->with('success', __('events.Mission brief acknowledged. You may now sign up for this event.'));
    }
}
