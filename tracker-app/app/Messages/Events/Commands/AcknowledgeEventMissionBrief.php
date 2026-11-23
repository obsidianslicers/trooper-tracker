<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\Event;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Facades\DB;

/**
 * Records that a trooper read an event's mission brief, which lets them sign up when the event
 * requires it. Acknowledging again only moves the time forward.
 *
 * @method static void call(Event $event, Trooper $trooper)
 */
final class AcknowledgeEventMissionBrief extends Message
{
    public function __construct(
        private readonly Event $event,
        private readonly Trooper $trooper,
    ) {}

    public function handle(): void
    {
        DB::table('tt_event_mission_acks')->updateOrInsert(
            [
                'event_id' => $this->event->id,
                'trooper_id' => $this->trooper->id,
            ],
            [
                'acknowledged_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
