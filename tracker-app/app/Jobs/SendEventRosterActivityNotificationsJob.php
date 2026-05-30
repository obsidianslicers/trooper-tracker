<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Notifications\Admin\EventRosterActivityNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendEventRosterActivityNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly string $action,
    ) {}

    public function handle(): void
    {
        $event = $this->event_trooper->event_shift->event;
        $notification = new EventRosterActivityNotification($this->event_trooper, $this->action);

        Trooper::whereHas('event_watches', fn ($q) => $q->where('event_id', $event->id))
            ->where('id', '!=', $this->event_trooper->trooper_id)
            ->each(fn ($trooper) => $trooper->notify($notification));
    }
}
