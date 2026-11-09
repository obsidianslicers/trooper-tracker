<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Notifications;

use App\Models\TrooperNotification;
use Hyperdrive\Message;

/**
 * Command to mark a single push notification as read.
 *
 * Updates the read_at timestamp for a specific notification.
 *
 * @method static void call(TrooperNotification $notification)
 */
final class MarkPushNotificationAsRead extends Message
{
    public function __construct(
        private readonly TrooperNotification $notification
    ) {}

    /**
     * Execute the command to mark the notification as read.
     */
    public function handle(): void
    {
        $this->notification->markAsRead();
    }
}
