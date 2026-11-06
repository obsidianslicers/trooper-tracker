<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Listeners;

use App\Enums\FlashType;
use App\Events\EventWatchChanging;
use TroopTracker\Plugins\Xenforo\Models\EventThread;
use TroopTracker\Plugins\Xenforo\Services\XenforoService;

/**
 * Watching an event also watches its forum thread, for troopers with a linked
 * forum account. If the forum refuses, the watch is left as it was.
 */
class SyncThreadWatch
{
    public function __construct(private readonly XenforoService $xenforo) {}

    public function handle(EventWatchChanging $changing): ?bool
    {
        $thread = EventThread::linked($changing->event->id);

        if ($thread === null || !XenforoService::isConfigured())
        {
            return null;
        }

        $user_id = $this->xenforo->resolve_user_id_for_trooper($changing->trooper->id);

        if ($user_id === null)
        {
            return null;
        }

        $result = $changing->watching
            ? $this->xenforo->watch_thread((int) $thread->thread_id, $user_id)
            : $this->xenforo->unwatch_thread((int) $thread->thread_id, $user_id);

        $status = (int) ($result['status'] ?? 0);

        if ($status < 200 || $status >= 300)
        {
            FlashType::danger('Unable to update your forum thread subscription. Please try again.');

            return false;
        }

        return null;
    }
}
