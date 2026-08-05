<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Models\Trooper;
use Exception;
use Illuminate\Notifications\Notification;

class BaseNotification extends Notification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = null;

    public function via(Trooper $notifiable): array
    {
        if ($this->notification_category === null)
        {
            throw new Exception('Notification category ['.get_class($this).'] is not set.');
        }

        if (is_string($this->notification_category))
        {
            $category = $this->notification_category;
        }
        else
        {
            $category = $this->notification_category->value;
        }

        $channels = [];

        if ($notifiable->wantsNotification($category, 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled && $notifiable->wantsNotification($category, 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid() && $notifiable->wantsNotification($category, 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
