<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bus\MagicBus;
use App\Features\Events\Queries\GetEventShiftsToRemindQuery;
use App\Notifications\Events\EventShiftCompletedNotification;
use Illuminate\Console\Command;

/**
 * Artisan command to remind troopers about closed event shifts.
 *
 * This command orchestrates the process of identifying closed event shifts
 * and sending reminder notifications to troopers who signed up as GOING or
 * TENTATIVE, prompting them to update their attendance status.
 */
class RemindClosedEventShiftsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:remind-closed-event-shifts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remind event troopers about closed event shifts';

    /**
     * Execute the console command.
     *
     * Orchestrates the process of reminding event troopers about closed shifts by:
     * 1. Dispatching a query to retrieve closed shifts needing reminders
     * 2. Updating last_notified_at on each shift
     * 3. Sending reminder notifications to troopers with GOING or TENTATIVE status
     *
     * @param  MagicBus  $bus  The message bus for dispatching queries
     */
    public function handle(MagicBus $bus): void
    {
        $event_shifts = $bus->send(new GetEventShiftsToRemindQuery());

        foreach ($event_shifts as $event_shift)
        {
            $event_shift->last_notified_at = now();
            $event_shift->save();

            //  EMAIL DAH TROOPAHZ!
            foreach ($event_shift->event_troopers as $event_trooper)
            {
                if ($event_trooper->intendsToGo())
                {
                    $event_trooper->trooper->notify(new EventShiftCompletedNotification($event_trooper));
                }
            }
        }
    }
}
