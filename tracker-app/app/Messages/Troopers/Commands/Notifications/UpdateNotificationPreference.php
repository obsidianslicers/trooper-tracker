<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Notifications;

use App\Enums\AdministrativeNotifications;
use App\Enums\NotificationChannels;
use App\Enums\TrooperNotifications;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Command message for updating a trooper's notification preferences.
 *
 * Plugin notification categories arrive as plain strings (validated by the form request).
 *
 * @method static void call(Trooper $trooper, AdministrativeNotifications|TrooperNotifications|string $notification, NotificationChannels $channel, bool $enabled)
 */
final class UpdateNotificationPreference extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly AdministrativeNotifications|TrooperNotifications|string $notification,
        private readonly NotificationChannels $channel,
        private readonly bool $enabled,
    ) {}

    /**
     * Execute the command to update trooper notification preferences.
     *
     * @return null
     */
    public function handle(): void
    {
        $preferences = $this->trooper->notification_preferences;

        if ($preferences === null || $preferences === [])
        {
            $preferences = [];
        }

        $category = is_string($this->notification) ? $this->notification : $this->notification->value;

        $preferences[$category][$this->channel->value] = $this->enabled;

        $this->trooper->notification_preferences = $preferences;

        $this->trooper->save();
    }
}
