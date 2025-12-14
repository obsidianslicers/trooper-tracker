<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Console\Command;

/**
 * Artisan command to calculate and store trooper achievements based on their event history.
 *
 * This command aggregates event data for each trooper, such as total troops,
 * volunteer hours, and funds raised, and then updates their corresponding
 * achievements in the database.
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
    protected $description = 'Close events.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $events = Event::active()->get();

        foreach ($events as $event)
        {
            $event->status = EventStatus::CLOSED;
            $event->save();
        }
    }
}
