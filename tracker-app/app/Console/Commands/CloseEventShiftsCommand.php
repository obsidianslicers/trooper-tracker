<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\EventShift;
use Illuminate\Console\Command;

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
        $event_shifts = EventShift::active()->get();

        foreach ($event_shifts as $event_shift)
        {
            $event_shift->status = EventStatus::CLOSED;
            $event_shift->save();
        }
    }
}
