<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bus\MagicBus;
use App\Enums\EventStatus;
use App\Features\Events\Queries\GetEventsToCloseQuery;
use App\Services\Forums\XenforoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Artisan command to close events that have ended.
 *
 * This command orchestrates the process of identifying and closing events
 * whose end date has passed. It delegates the query logic to GetEventsToCloseQuery
 * service and updates each event's status to CLOSED.
 */
class MergeTrooperAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:merge-trooper-accounts
        {target_trooper_id : ID of the trooper whose account will be merged into}
        {source_trooper_id : ID of the trooper whose account will be merged from}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge trooper accounts';

    /**
     * Execute the console command.
     *
     * Orchestrates the event closing process by:
     * 1. Dispatching GetEventsToCloseQuery to retrieve active events that have ended
     * 2. Updating each event's status to CLOSED
     *
     * @return int
     */
    public function handle(): int
    {
        $target_id = (int) $this->argument('target_trooper_id');

        if ($target_id <= 0)
        {
            $this->error('Please provide a valid target_trooper_id.');

            return self::FAILURE;
        }

        $source_id = (int) $this->argument('source_trooper_id');

        if ($source_id <= 0)
        {
            $this->error('Please provide a valid source_trooper_id, or use --revert.');

            return self::FAILURE;
        }


        return self::SUCCESS;
    }
}
