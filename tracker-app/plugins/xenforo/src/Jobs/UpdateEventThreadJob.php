<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Jobs;

use App\Facades\TroopTrackerFacade;
use App\Models\Event;
use App\Services\Forums\XenforoService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use TroopTracker\Plugins\Xenforo\Models\EventThread;
use TroopTracker\Plugins\Xenforo\Services\ForumThreadMessageService;

class UpdateEventThreadJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 300;

    private ?int $event_id;

    public function __construct(?int $event_id = null)
    {
        $this->event_id = $event_id;
    }

    public function handle(XenforoService $xenforo, ForumThreadMessageService $forumThreadMessageService): void
    {
        if (! TroopTrackerFacade::isXenforoIntegrationConfigured())
        {
            return;
        }

        if ($this->event_id !== null)
        {
            $thread = EventThread::linked($this->event_id);

            if ($thread !== null && $thread->hasFirstPost())
            {
                $this->syncEvent($thread, $xenforo, $forumThreadMessageService);
            }

            return;
        }

        $cutoff = now()->subMinute();

        EventThread::query()
            ->with('event')
            ->where(EventThread::SHOULD_CREATE_THREAD, true)
            ->whereNotNull(EventThread::THREAD_ID)
            ->whereNotNull(EventThread::POST_ID)
            ->whereHas('event', fn ($query) => $query->where(Event::EVENT_END, '>=', $cutoff))
            ->chunkById(50, function ($threads) use ($forumThreadMessageService, $xenforo): void
            {
                foreach ($threads as $thread)
                {
                    $this->syncEvent($thread, $xenforo, $forumThreadMessageService);
                }
            });
    }

    public function uniqueId(): string
    {
        return $this->event_id === null
            ? 'forum-thread-sync:all'
            : 'forum-thread-sync:event:'.$this->event_id;
    }

    private function syncEvent(
        EventThread $thread,
        XenforoService $xenforo,
        ForumThreadMessageService $forumThreadMessageService
    ): void {
        $event = $thread->event;
        $message = $forumThreadMessageService->buildThreadMessage($event);

        $userId = $xenforo->resolve_user_id_for_trooper($event->created_id);

        $xenforo->update_thread((int) $thread->thread_id, (string) $event->name);
        $xenforo->update_post((int) $thread->post_id, $message, $userId);
    }
}
