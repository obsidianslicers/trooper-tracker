<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Threads;

use App\Facades\TroopTrackerFacade;
use App\Models\Event;
use App\Services\Forums\XenforoService;
use TroopTracker\Plugins\Xenforo\Models\EventThread;

/**
 * What the event page's Forum Comments section shows.
 */
final class EventComments
{
    /**
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $smilies = null;

    public function __construct(private readonly XenforoService $xenforo) {}

    public function thread(Event $event): ?EventThread
    {
        return TroopTrackerFacade::isXenforoIntegrationConfigured()
            ? EventThread::linked($event->id)
            : null;
    }

    /**
     * Replies in the event's thread, leaving out its first post (the event itself).
     *
     * @return array<int, array<string, mixed>>
     */
    public function posts(?EventThread $thread): array
    {
        if ($thread === null || !$thread->hasFirstPost())
        {
            return [];
        }

        return $this->xenforo->get_thread_posts(
            (int) $thread->thread_id,
            exclude_post_id: (int) $thread->post_id,
            per_page: 50,
            max_pages: 20,
        );
    }

    /**
     * The forum's smilies, for the reply box and the event comments editor.
     *
     * @return array<int, array<string, mixed>>
     */
    public function smilies(): array
    {
        if (!TroopTrackerFacade::isXenforoIntegrationConfigured())
        {
            return [];
        }

        return $this->smilies ??= $this->xenforo->get_smilies();
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('services.xenforo.base_url', ''), '/');
    }
}
