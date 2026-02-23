<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bus\MagicBus;
use App\Features\Events\Commands\SendEventCreatedNotificationCommand;
use App\Features\Events\Queries\GetTroopersForEventCreatedQuery;
use App\Models\Event;
use App\Services\Forums\XenforoService;
use App\Services\Notifications\DiscordNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Creates event notifications for active troopers when a new event is posted.
 *
 * This job generates EventNotification records for all active troopers who have
 * valid email addresses and haven't already been notified about the event.
 * Troopers with instant notification preferences receive emails immediately,
 * while others have their notifications queued for batch processing.
 */
class SendEventCreatedNotificationsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  Event  $event  The event to create notifications for.
     */
    public function __construct(private readonly Event $event)
    {
        //
    }

    /**
     * Execute the job.
     *
     * Processes all active troopers and creates event notifications based on their
     * notification preferences. Troopers with instant notifications receive emails
     * immediately, while others have notifications queued for later batch processing.
     * Also notifies Discord about the new event with the organization name.
     *
     * @param  MagicBus  $bus  The message bus for sending queries and commands.
     * @param  DiscordNotifier  $notifier  The Discord notification service.
     */
    public function handle(MagicBus $bus, DiscordNotifier $notifier, XenforoService $xenforo): void
    {
        // Trooper notifications: only send if not already sent
        if ($this->event->create_notifications_sent_at === null)
        {
            $troopers_query = new GetTroopersForEventCreatedQuery($this->event);
            $troopers = $bus->send($troopers_query);
            foreach ($troopers as $trooper)
            {
                $send_notification_command = new SendEventCreatedNotificationCommand($this->event, $trooper);
                $bus->send($send_notification_command);
            }
            $this->event->create_notifications_sent_at = now();
            $this->event->save();
        }

        // Forum posting: always run
        $organization = $this->event->organization;
        $organization_name = $organization->name;

        // Discord notification: always run
        $notifier->sendEventNotification($this->event->id, $this->event->name, $this->event->comments ?? null, $organization_name);

        // XenForo thread creation: only if related forum is configured
        if (! empty($organization->related_forum))
        {
            $node_id = (int) $organization->related_forum;
            $title = $this->event->name;

            // Use ForumBBCodeHelper to generate BBCode message
            $message = \App\Helpers\ForumBBCodeHelper::threadTemplate($this->event);

            // Let XenforoService resolve the XenForo user ID (via OAuth) for the event creator
            $xenforo_user_id = $xenforo->resolve_user_id_for_trooper($this->event->created_id);

            $result = $xenforo->create_thread($node_id, $title, $message, $xenforo_user_id);

            if (($result['status'] ?? 0) < 200 || ($result['status'] ?? 0) >= 300)
            {
                app('\Illuminate\Support\Facades\Log')::warning('Failed to create XenForo thread for event', [
                    'event' => $this->event->id,
                    'org' => $organization->id,
                    'status' => $result['status'] ?? null,
                    'body' => $result['body'] ?? null,
                ]);
            }
        }
    }
}
