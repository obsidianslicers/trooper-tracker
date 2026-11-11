<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Discord\Listeners;

use App\Events\EventPosted;
use Illuminate\Support\Facades\Log;
use Throwable;
use TroopTracker\Plugins\Discord\DiscordNotifier;

/**
 * Posts a newly posted event to Discord.
 *
 * Failures are logged rather than thrown so that a Discord outage cannot stop
 * the rest of the event-posted work (such as the forum thread) or cause the
 * job to retry and notify troopers twice.
 */
class AnnounceEventOnDiscord
{
    public function __construct(private readonly DiscordNotifier $notifier) {}

    public function handle(EventPosted $posted): void
    {
        $event = $posted->event;

        try
        {
            $this->notifier->sendEventNotification(
                $event->id,
                $event->name,
                $event->comments ?? null,
                $event->organization,
                $event->notCountedNotice(),
            );
        }
        catch (Throwable $e)
        {
            Log::warning(
                "Discord announcement failed for event [{$event->id}]: {$e->getMessage()}"
            );
        }
    }
}
