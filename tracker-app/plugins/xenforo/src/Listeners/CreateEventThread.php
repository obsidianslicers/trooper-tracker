<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Listeners;

use App\Events\EventPosted;
use App\Facades\TroopTrackerFacade;
use App\Services\Forums\XenforoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use TroopTracker\Plugins\Xenforo\Models\EventThread;
use TroopTracker\Plugins\Xenforo\Models\OrganizationForum;
use TroopTracker\Plugins\Xenforo\Services\ForumThreadMessageService;

/**
 * Opens a forum thread for a newly posted event in its organization's forum.
 */
class CreateEventThread implements ShouldQueue
{
    public function __construct(
        private readonly XenforoService $xenforo,
        private readonly ForumThreadMessageService $messages,
    ) {}

    public function handle(EventPosted $posted): void
    {
        $event = $posted->event;
        $organization = $event->organization;
        $forum_id = OrganizationForum::forOrganization($organization?->id)?->event_forum_id;
        $thread = EventThread::forEvent($event->id);

        if (
            !TroopTrackerFacade::isXenforoIntegrationConfigured()
            || empty($forum_id)
            || ($thread !== null && (!$thread->should_create_thread || !empty($thread->thread_id)))
        ) {
            return;
        }

        $result = $this->xenforo->create_thread(
            $forum_id,
            $event->name,
            $this->messages->buildThreadMessage($event),
            $this->xenforo->resolve_user_id_for_trooper($event->created_id),
        );

        $status = (int) ($result['status'] ?? 0);

        if ($status < 200 || $status >= 300)
        {
            Log::warning('Failed to create XenForo thread for event', [
                'event' => $event->id,
                'org' => $organization->id,
                'status' => $result['status'] ?? null,
                'body' => $result['body'] ?? null,
            ]);

            return;
        }

        $this->storeThread($event->id, $result['body']['thread'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $thread
     */
    private function storeThread(int $event_id, array $thread): void
    {
        $values = array_filter([
            EventThread::THREAD_ID => is_numeric($thread['thread_id'] ?? null) ? (int) $thread['thread_id'] : null,
            EventThread::POST_ID => is_numeric($thread['first_post_id'] ?? null) ? (int) $thread['first_post_id'] : null,
        ]);

        if ($values !== [])
        {
            EventThread::store($event_id, $values);
        }
    }
}
