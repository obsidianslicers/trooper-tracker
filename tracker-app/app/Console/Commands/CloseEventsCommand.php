<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Services\Events\GetEventsToCloseQuery;
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
     * 1. Querying for active events that have ended via GetEventsToCloseQuery
     * 2. Updating each event's status to CLOSED
     *
     * @param GetEventsToCloseQuery $get_events_to_close Service to retrieve events needing closure
     * @return void
     */
    public function handle(GetEventsToCloseQuery $get_events_to_close): void
    {
        $events = $get_events_to_close();

        foreach ($events as $event)
        {
            $event->status = EventStatus::CLOSED;
            $event->save();
        }
    }
}
