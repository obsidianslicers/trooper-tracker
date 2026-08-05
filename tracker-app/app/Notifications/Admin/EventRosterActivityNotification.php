<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Enums\RosterAction;
use App\Mail\Admin\Events\EventRosterActivityMail;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Notifications\QueueableNotification;

class EventRosterActivityNotification extends QueueableNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'event_roster_activity';

    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly RosterAction $action,
    ) {
    }

    public function toMail(Trooper $notifiable): EventRosterActivityMail
    {
        return (new EventRosterActivityMail($this->event_trooper, $this->action))
            ->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $event = $this->event_trooper->event_shift->event;
        $trooper = $this->event_trooper->trooper;

        $verb = match ($this->action)
        {
            RosterAction::CANCELLED => 'cancelled from',
            RosterAction::RESIGNED_UP => 're-signed up for',
            default => 'signed up for',
        };

        return [
            'title' => 'Roster Update: ' . $event->name,
            'body' => $trooper->display_name . ' has ' . $verb . ' this event.',
            'url' => '/events/details/' . $event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
