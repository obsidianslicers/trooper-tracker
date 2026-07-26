<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Trooper;
use Illuminate\Console\Command;
use App\Messages\Troopers\Commands\Merge\MergeTroopers;
/**
 * Artisan command to close events that have ended.
 *
 * This command orchestrates the process of identifying and closing events
 * whose end date has passed. It delegates the query logic to GetEventsToCloseQuery
 * service and updates each event's status to CLOSED.
 */
class MergeTroopersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:merge-troopers
        {target_trooper_id : ID of the trooper whose permissions will be changed}
        {source_trooper_id : ID of the trooper whose permissions will be copied}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge troopers';

    /**
     * Execute the console command.
     *
     * Orchestrates the trooper merging process by:
     * 1. Identifying the source and target troopers
     * 2. Merging all relationships from the source trooper into the target trooper
     *
     */
    public function handle(): void
    {
        MergeTroopers::call(
            source_trooper: Trooper::findOrFail($this->argument('source_trooper_id')),
            target_trooper: Trooper::findOrFail($this->argument('target_trooper_id')),
        );
    }
}
