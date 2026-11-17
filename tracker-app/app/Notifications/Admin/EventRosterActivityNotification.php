<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Enums\AdministrativeNotifications;
use App\Enums\RosterAction;
use App\Enums\TrooperNotifications;
use App\Mail\Admin\Events\EventRosterActivityMail;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

class EventRosterActivityNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category =
        TrooperNotifications::EVENT_ROSTER_ACTIVITY;

    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly RosterAction $action,
    ) {}

    public function toMail(Trooper $notifiable): EventRosterActivityMail
    {
        return (new EventRosterActivityMail($this->event_trooper, $this->action))
            ->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $event = $this->event_trooper->event_shift->event;
        $trooper = $this->event_trooper->trooper;

        $body = match ($this->action)
        {
            RosterAction::CANCELLED => 'notifications.:trooper has cancelled from this event.',
            RosterAction::RESIGNED_UP => 'notifications.:trooper has re-signed up for this event.',
            default => 'notifications.:trooper has signed up for this event.',
        };

        return [
            'title' => __('notifications.Roster Update: :name', ['name' => $event->name]),
            'body' => __($body, ['trooper' => $trooper->display_name]),
            'url' => '/events/details/'.$event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
