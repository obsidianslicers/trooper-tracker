<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\MergeTroopersJob;
use App\Models\Trooper;
use Illuminate\Console\Command;

/**
 * Artisan command to merge troopers.
 *
 * This command orchestrates the process of merging two trooper accounts.
 * It delegates the merging logic to the MergeTroopers command.
 */
class MergeTroopersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:merge-troopers
        {source_trooper_id : ID of the trooper whose account will be merged from}
        {target_trooper_id : ID of the trooper whose account will be merged into}';

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
     */
    public function handle(): void
    {
        $source_trooper = Trooper::findOrFail($this->argument('source_trooper_id'));
        $target_trooper = Trooper::findOrFail($this->argument('target_trooper_id'));

        $job = new MergeTroopersJob(
            source_trooper: $source_trooper,
            target_trooper: $target_trooper
        );

        $job->handle();
    }
}
