<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Notifications\DiscordNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendDiscordEventNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $eventId;
    public string $title;
    public ?string $description;
    /** @var int|string|null */
    public $squad;

    public function __construct(int $eventId, string $title, ?string $description, $squad)
    {
        $this->eventId = $eventId;
        $this->title = $title;
        $this->description = $description;
        $this->squad = $squad;
    }

    public function handle(DiscordNotifier $notifier): void
    {
        $notifier->sendEventNotification($this->eventId, $this->title, $this->description, $this->squad);
    }
}
