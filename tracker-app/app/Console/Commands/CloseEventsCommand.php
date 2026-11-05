<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Events\EventClosed;
use App\Messages\Events\Queries\GetEventsToClose;
use Illuminate\Console\Command;

/**
 * Artisan command to close events that have ended.
 *
 * This command orchestrates the process of identifying and closing events
 * whose end date has passed. It delegates the query logic to GetEventsToClose
 * service and updates each event's status to CLOSED.
 */
class CloseEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:close-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close events that have ended';

    /**
     * Close each event that has ended. Listeners to EventClosed handle anything
     * else, such as a plugin archiving the event's forum thread.
     */
    public function handle(): void
    {
        foreach (GetEventsToClose::call() as $event)
        {
            $event->status = EventStatus::CLOSED;
            $event->save();

            EventClosed::dispatch($event);
        }
    }
}
