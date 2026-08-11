<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Enums\NotificationChannels;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Command message for updating a trooper's notification preferences.
 * 
 * @method static void call(Trooper $trooper, AdministrativeNotifications|TrooperNotifications $notification, NotificationChannels $channel, bool $enabled)
 */
final class UpdateNotificationPreference extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly AdministrativeNotifications|TrooperNotifications $notification,
        private readonly NotificationChannels $channel,
        private readonly bool $enabled,
    ) {
    }

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

        $preferences[$this->notification->value][$this->channel->value] = $this->enabled;

        $this->trooper->notification_preferences = $preferences;

        $this->trooper->save();
    }
}
