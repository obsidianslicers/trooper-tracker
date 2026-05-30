<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Event;
use App\Models\Trooper;
use App\Notifications\Events\EventUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendEventUpdatedNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Event $event,
        private readonly array $changed_fields,
    ) {}

    public function handle(): void
    {
        $notification = new EventUpdatedNotification($this->event, $this->changed_fields);

        Trooper::whereHas('event_watches', fn ($q) => $q->where('event_id', $this->event->id))
            ->each(fn ($trooper) => $trooper->notify($notification));
    }
}
