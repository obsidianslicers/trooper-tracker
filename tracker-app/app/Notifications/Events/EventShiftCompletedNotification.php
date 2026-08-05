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
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'event_shift_completed';

    public function __construct(private readonly EventTrooper $event_trooper) {}

    public function toMail(Trooper $notifiable): EventShiftComplete
    {
        return (new EventShiftComplete($this->event_trooper))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $event_id = $this->event_trooper->event_shift->event->id;
        $shift_id = $this->event_trooper->event_shift->id;
        $name = $this->event_trooper->event_shift->event->name;
        $time_display = $this->event_trooper->event_shift->time_display;

        return [
            'title' => "Event Shift Completed for {$name}",
            'body' => "This event shift {$time_display} has completed.",
            'url' => "/events/details/{$event_id}#shift-{$shift_id}",
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
