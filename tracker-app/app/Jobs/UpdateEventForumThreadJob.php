<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\ForumBBCodeHelper;
use App\Models\Event;
use App\Services\Forums\XenforoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateEventForumThreadJob implements ShouldQueue
{
    use Queueable;

    public function handle(XenforoService $xenforo): void
    {
        $now = now();
        $cutoff = $now->copy()->subMinute();

        Event::query()
            ->whereNotNull(Event::THREAD_ID)
            ->whereNotNull(Event::POST_ID)
            ->where(Event::CREATE_FORUM_THREAD, '!=', false)
            ->where(Event::EVENT_END, '>=', $cutoff)
            ->chunkById(50, function ($events) use ($xenforo): void {
                foreach ($events as $event)
                {
                    $roster = ForumBBCodeHelper::rosterSummary($event);
                    $message = ForumBBCodeHelper::threadTemplate($event, $roster);

                    $userId = $xenforo->resolve_user_id_for_trooper($event->created_id);

                    $xenforo->update_post((int) $event->post_id, $message, $userId);
                }
            });
    }
}
