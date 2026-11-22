<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Mail\Events\EventShiftComplete;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

class EventShiftCompletedNotification extends BaseNotification
{
    /**
     * The key in the notification's data naming the sign-up, so ForgetAttendanceReminders can find it.
     */
    public const EVENT_TROOPER_ID = 'event_trooper_id';

    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'event_shift_completed';

    public function __construct(private readonly EventTrooper $event_trooper) {}

    public function toMail(Trooper $notifiable): EventShiftComplete
    {
        return (new EventShiftComplete($this->event_trooper))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $name = $this->event_trooper->event_shift->event->name;
        $time_display = $this->event_trooper->event_shift->time_display;

        return [
            'title' => __('notifications.Event Shift Completed for :name', ['name' => $name]),
            'body' => __('notifications.This event shift :time has completed.', ['time' => $time_display]),
            'url' => route('events.troops-to-confirm', absolute: false),
            self::EVENT_TROOPER_ID => $this->event_trooper->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
