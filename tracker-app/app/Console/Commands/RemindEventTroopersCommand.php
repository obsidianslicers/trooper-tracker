<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bus\MagicBus;
use App\Enums\EventStatus;
use App\Features\Events\Queries\GetEventShiftsToCloseQuery;
use App\Notifications\Events\EventShiftCompletedNotification;
use Illuminate\Console\Command;

/**
 * Artisan command to close event shifts that have ended.
 *
 * This command orchestrates the process of identifying and closing event shifts
 * whose end time has passed. It delegates the query logic to GetEventShiftsToCloseQuery
 * service, updates each shift's status to CLOSED, and sends completion emails to
 * troopers who attended.
 */
class RemindEventTroopersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:remind-event-troopers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remind event troopers about upcoming shifts';

    /**
     * Execute the console command.
     *
     * Orchestrates the process of reminding event troopers about upcoming shifts by:
     * 1. Dispatching a query to retrieve upcoming event shifts
     * 2. Sending reminder notifications to troopers with GOING status
     *
     * @param  MagicBus  $bus  The message bus for dispatching queries
     */
    public function handle(MagicBus $bus): void
    {
        $event_shifts = $bus->send(new GetRemindEventShiftsQuery);

        foreach ($event_shifts as $event_shift)
        {
            $event_shift->status = EventStatus::CLOSED;
            $event_shift->save();

            //  EMAIL DAH TROOPAHZ!
            foreach ($event_shift->event_troopers as $event_trooper)
            {
                if ($event_trooper->is_going)
                {
                    $event_trooper->trooper->notify(new EventShiftReminderNotification($event_trooper));
                }
            }
        }
    }
}
