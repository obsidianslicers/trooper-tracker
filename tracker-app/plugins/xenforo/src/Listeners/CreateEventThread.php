<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Listeners;

use App\Events\EventPosted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use TroopTracker\Plugins\Xenforo\Models\EventThread;
use TroopTracker\Plugins\Xenforo\Models\EventTypeForum;
use TroopTracker\Plugins\Xenforo\Models\OrganizationForum;
use TroopTracker\Plugins\Xenforo\Services\ForumThreadMessageService;
use TroopTracker\Plugins\Xenforo\Services\XenforoService;
use TroopTracker\Plugins\Xenforo\Support\ForumThreadTitle;

/**
 * Opens a forum thread for a newly posted event in its event type's forum, or else its
 * organization's.
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
        $forum_id = EventTypeForum::forEvent($event)?->event_forum_id
            ?: OrganizationForum::forOrganization($event->organization?->id)?->event_forum_id;

        if (empty($forum_id) || !$this->shouldCreate($event->id))
        {
            return;
        }

        $result = $this->xenforo->create_thread(
            $forum_id,
            ForumThreadTitle::for($event),
            $this->messages->buildThreadMessage($event),
            $this->xenforo->resolve_user_id_for_trooper($event->created_id),
        );

        $status = (int) ($result['status'] ?? 0);

        if ($status < 200 || $status >= 300)
        {
            Log::warning('Failed to create XenForo thread for event', [
                'event' => $event->id,
                'org' => $event->organization?->id,
                'status' => $result['status'] ?? null,
                'body' => $result['body'] ?? null,
            ]);

            return;
        }

        $this->storeThread($event->id, $result['body']['thread'] ?? []);
    }

    private function shouldCreate(int $event_id): bool
    {
        $thread = EventThread::forEvent($event_id);

        return XenforoService::isConfigured()
            && ($thread === null || ($thread->should_create_thread && empty($thread->thread_id)));
    }

    /**
     * @param  array<string, mixed>  $thread
     */
    private function storeThread(int $event_id, array $thread): void
    {
        $values = array_filter([
            EventThread::THREAD_ID => $this->numberOrNull($thread['thread_id'] ?? null),
            EventThread::POST_ID => $this->numberOrNull($thread['first_post_id'] ?? null),
        ]);

        if ($values !== [])
        {
            EventThread::store($event_id, $values);
        }
    }

    private function numberOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
