<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Mail\Events\EventShiftComplete;
use App\Models\EventShift;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Artisan command to calculate and store trooper achievements based on their event history.
 *
 * This command aggregates event data for each trooper, such as total troops,
 * volunteer hours, and funds raised, and then updates their corresponding
 * achievements in the database.
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
    protected $description = 'Close event shifts.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $with = [
            'event.organization',
            'event_troopers.trooper',
        ];

        $event_shifts = EventShift::with($with)
            ->active()
            ->where(EventShift::SHIFT_ENDS_AT, '<', now())
            ->get();

        foreach ($event_shifts as $event_shift)
        {
            $event_shift->status = EventStatus::CLOSED;
            $event_shift->save();

            //  EMAIL DAH TROOPERZ!
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
