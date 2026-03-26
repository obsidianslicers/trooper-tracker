<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Facades\TroopTrackerFacade;
use App\Models\Event;
use App\Services\Forums\ForumThreadMessageService;
use App\Services\Forums\XenforoService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateEventForumThreadJob implements ShouldQueue, ShouldBeUnique
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
            $event = Event::query()
                ->whereKey($this->event_id)
                ->whereNotNull(Event::THREAD_ID)
                ->whereNotNull(Event::POST_ID)
                ->where(Event::CREATE_FORUM_THREAD, '!=', false)
                ->first();

            if ($event !== null)
            {
                $this->syncEvent($event, $xenforo, $forumThreadMessageService);
            }

            return;
        }

        $now = now();
        $cutoff = $now->copy()->subMinute();

        Event::query()
            ->whereNotNull(Event::THREAD_ID)
            ->whereNotNull(Event::POST_ID)
            ->where(Event::CREATE_FORUM_THREAD, '!=', false)
            ->where(Event::EVENT_END, '>=', $cutoff)
            ->chunkById(50, function ($events) use ($forumThreadMessageService, $xenforo): void {
                foreach ($events as $event)
                {
                    $this->syncEvent($event, $xenforo, $forumThreadMessageService);
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
        Event $event,
        XenforoService $xenforo,
        ForumThreadMessageService $forumThreadMessageService
    ): void {
        $message = $forumThreadMessageService->buildThreadMessage($event);

        $userId = $xenforo->resolve_user_id_for_trooper($event->created_id);

        $xenforo->update_thread((int) $event->thread_id, (string) $event->name);
        $xenforo->update_post((int) $event->post_id, $message, $userId);
    }
}
