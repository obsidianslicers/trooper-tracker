<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bus\MagicBus;
use App\Enums\EventStatus;
use App\Features\Events\Queries\GetEventsToCloseQuery;
use Illuminate\Console\Command;

/**
 * Artisan command to close events that have ended.
 *
 * This command orchestrates the process of identifying and closing events
 * whose end date has passed. It delegates the query logic to GetEventsToCloseQuery
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
     * Execute the console command.
     *
     * Orchestrates the event closing process by:
     * 1. Dispatching GetEventsToCloseQuery to retrieve active events that have ended
     * 2. Updating each event's status to CLOSED
     *
     * @param MagicBus $bus The message bus for dispatching queries
     * @return void
     */
    public function handle(MagicBus $bus): void
    {
        $events = $bus->send(new GetEventsToCloseQuery());

        foreach ($events as $event)
        {
            $event->status = EventStatus::CLOSED;
            $event->save();
        }
    }
}
