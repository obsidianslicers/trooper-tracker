<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bus\MagicBus;
use App\Features\Events\Commands\SendEventCreatedNotificationCommand;
use App\Features\Events\Queries\GetTroopersForEventCreatedQuery;
use App\Models\Event;
use App\Services\Forums\ForumThreadMessageService;
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
    public function handle(
        MagicBus $bus,
        DiscordNotifier $notifier,
        ForumThreadMessageService $forumThreadMessageService,
        XenforoService $xenforo
    ): void
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

        // Forum posting / notifications
        $organization = $this->event->organization;

        // Discord notification: always run
        $notifier->sendEventNotification($this->event->id, $this->event->name, $this->event->comments ?? null, $organization);

        // XenForo thread creation: only if related forum is configured, the event
        // is configured to create a forum thread, and a thread has not already
        // been created for this event.
        if (
            ! empty($organization->related_forum) &&
            $this->event->create_forum_thread !== false &&
            empty($this->event->thread_id)
        ) {
            $node_id = (int) $organization->related_forum;
            $title = $this->event->name;

            $message = $forumThreadMessageService->buildThreadMessage($this->event);

            // Let XenforoService resolve the XenForo user ID (via OAuth) for the event creator
            $xenforo_user_id = $xenforo->resolve_user_id_for_trooper($this->event->created_id);

            $result = $xenforo->create_thread($node_id, $title, $message, $xenforo_user_id);

            if (($result['status'] ?? 0) >= 200 && ($result['status'] ?? 0) < 300)
            {
                // Attempt to capture the created thread/post IDs from the XenForo API
                $body = $result['body'] ?? [];
                if (isset($body['thread']['thread_id']) && is_numeric($body['thread']['thread_id']))
                {
                    $thread_id = (int) $body['thread']['thread_id'];
                    $this->event->thread_id = $thread_id;
                }

                if (isset($body['thread']['first_post_id']) && is_numeric($body['thread']['first_post_id']))
                {
                    $post_id = (int) $body['thread']['first_post_id'];
                    $this->event->post_id = $post_id;
                }

                if ($this->event->isDirty(['thread_id', 'post_id']))
                {
                    $this->event->save();
                }
            }
            else
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
