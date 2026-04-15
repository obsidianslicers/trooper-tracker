<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Records a trooper's acknowledgement of an event mission brief.
 */
class MissionBriefAcknowledgeController extends MagicBusController
{
    public function __invoke(Request $request, Event $event): RedirectResponse
    {
        $trooper = $request->user();

        // Only authenticated troopers can acknowledge mission briefs
        if ($trooper === null)
        {
            return redirect()->route('auth.login');
        }

        DB::table('tt_event_mission_acks')->updateOrInsert(
            [
                'event_id' => $event->id,
                'trooper_id' => $trooper->id,
            ],
            [
                'acknowledged_at' => now(),
                'updated_at' => now(),
            ]
        );

        return redirect()->route('events.display', compact('event'))
            ->with('success', 'Mission brief acknowledged. You may now sign up for this event.');
    }
}
