<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bus\MagicBus;
use App\Enums\EventStatus;
use App\Features\Events\Queries\GetEventShiftsToCloseQuery;
use App\Mail\Events\EventShiftComplete;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Artisan command to close event shifts that have ended.
 *
 * This command orchestrates the process of identifying and closing event shifts
 * whose end time has passed. It delegates the query logic to GetEventShiftsToCloseQuery
 * service, updates each shift's status to CLOSED, and sends completion emails to
 * troopers who attended.
 */
class CloseEventShiftsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:close-event-shifts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close event shifts that have ended and notify attending troopers';

    /**
     * Execute the console command.
     *
     * Orchestrates the event shift closing process by:
     * 1. Dispatching GetEventShiftsToCloseQuery to retrieve active event shifts that have ended
     * 2. Updating each shift's status to CLOSED
     * 3. Sending EventShiftComplete emails to troopers with GOING status
     *
     * @param  MagicBus  $bus  The message bus for dispatching queries
     */
    public function handle(MagicBus $bus): void
    {
        $event_shifts = $bus->send(new GetEventShiftsToCloseQuery);

        foreach ($event_shifts as $event_shift)
        {
            $event_shift->status = EventStatus::CLOSED;
            $event_shift->save();

            //  EMAIL DAH TROOPAHZ!
            foreach ($event_shift->event_troopers as $event_trooper)
            {
                if ($event_trooper->is_going)
                {
                    Mail::to($event_trooper->trooper->email)->queue(new EventShiftComplete($event_trooper));
                }
            }
        }
    }
}
