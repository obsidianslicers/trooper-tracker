<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Channels\FcmChannel;
use App\Enums\RosterAction;
use App\Mail\Admin\Events\EventRosterActivityMail;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class EventRosterActivityNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly RosterAction $action,
    ) {}

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('event_roster_activity', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('event_roster_activity', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('event_roster_activity', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
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
            'title' => 'Roster Update: '.$event->name,
            'body' => $trooper->display_name.' has '.$verb.' this event.',
            'url' => '/events/details/'.$event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
