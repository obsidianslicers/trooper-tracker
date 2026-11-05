<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Listeners;

use App\Events\EventClosed;
use App\Facades\TroopTrackerFacade;
use App\Services\Forums\XenforoService;
use Illuminate\Support\Facades\Log;
use TroopTracker\Plugins\Xenforo\Models\EventThread;

/**
 * Moves a closed event's forum thread to its organization's archive forum.
 */
class ArchiveEventThread
{
    public function __construct(private readonly XenforoService $xenforo) {}

    public function handle(EventClosed $closed): void
    {
        $event = $closed->event;
        $thread = EventThread::linked($event->id);

        if ($thread === null || !TroopTrackerFacade::isXenforoIntegrationConfigured())
        {
            return;
        }

        $organization = $event->organization ?? $event->primary_organization;

        if (!$organization || empty($organization->related_forum_archive))
        {
            return;
        }

        $target_node_id = (int) $organization->related_forum_archive;
        $result = $this->xenforo->move_thread((int) $thread->thread_id, $target_node_id);
        $status = (int) ($result['status'] ?? 0);

        if ($status < 200 || $status >= 300)
        {
            Log::warning('Failed to move XenForo thread to archive for event', [
                'event' => $event->id,
                'org' => $organization->id,
                'thread_id' => $thread->thread_id,
                'target_node_id' => $target_node_id,
                'status' => $result['status'] ?? null,
                'body' => $result['body'] ?? null,
            ]);
        }
    }
}
