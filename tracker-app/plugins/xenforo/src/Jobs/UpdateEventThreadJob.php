<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Jobs;

use App\Models\Event;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use TroopTracker\Plugins\Xenforo\Models\EventThread;
use TroopTracker\Plugins\Xenforo\Services\ForumThreadMessageService;
use TroopTracker\Plugins\Xenforo\Services\XenforoService;
use TroopTracker\Plugins\Xenforo\Support\ForumThreadTitle;

class UpdateEventThreadJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 300;

    private ?int $event_id;

    public function __construct(?int $event_id = null)
    {
        $this->event_id = $event_id;
    }

    public function handle(XenforoService $xenforo, ForumThreadMessageService $messages): void
    {
        if (! XenforoService::isConfigured())
        {
            return;
        }

        if ($this->event_id === null)
        {
            $this->syncRecentEvents($xenforo, $messages);

            return;
        }

        $thread = EventThread::linked($this->event_id);

        if ($thread !== null && $thread->hasFirstPost())
        {
            $this->syncEvent($thread, $xenforo, $messages);
        }
    }

    public function uniqueId(): string
    {
        return $this->event_id === null
            ? 'forum-thread-sync:all'
            : 'forum-thread-sync:event:'.$this->event_id;
    }

    private function syncRecentEvents(
        XenforoService $xenforo,
        ForumThreadMessageService $messages
    ): void {
        $cutoff = now()->subMinute();

        EventThread::query()
            ->with('event')
            ->where(EventThread::SHOULD_CREATE_THREAD, true)
            ->whereNotNull(EventThread::THREAD_ID)
            ->whereNotNull(EventThread::POST_ID)
            ->whereHas('event', fn ($query) => $query->where(Event::EVENT_END, '>=', $cutoff))
            ->chunkById(50, function ($threads) use ($messages, $xenforo): void
            {
                foreach ($threads as $thread)
                {
                    $this->syncEvent($thread, $xenforo, $messages);
                }
            });
    }

    private function syncEvent(
        EventThread $thread,
        XenforoService $xenforo,
        ForumThreadMessageService $messages
    ): void {
        $event = $thread->event;
        $message = $messages->buildThreadMessage($event);
        $user_id = $xenforo->resolve_user_id_for_trooper($event->created_id);

        $xenforo->update_thread((int) $thread->thread_id, ForumThreadTitle::for($event));
        $xenforo->update_post((int) $thread->post_id, $message, $user_id);
    }
}
