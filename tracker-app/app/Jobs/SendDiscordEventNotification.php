<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Notifications\DiscordNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Sends an event notification to Discord.
 *
 * Dispatches to the queue and notifies the DiscordNotifier service to broadcast
 * event information to configured Discord channels for the relevant squad/organization.
 */
final class SendDiscordEventNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  int  $event_id  The ID of the event to notify about.
     * @param  string  $title  The title of the event.
     * @param  string|null  $description  Optional description or comments about the event.
     * @param  int|string|null  $squad  The organization/squad name or ID for Discord role resolution.
     */
    public function __construct(
        public readonly int $event_id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly int|string|null $squad) {}

    /**
     * Handle the queued job.
     *
     * Delegates to the DiscordNotifier service to send the event notification
     * to configured Discord channels.
     *
     * @param  DiscordNotifier  $notifier  The Discord notification service.
     */
    public function handle(DiscordNotifier $notifier): void
    {
        $notifier->sendEventNotification($this->event_id, $this->title, $this->description, $this->squad);
    }
}
