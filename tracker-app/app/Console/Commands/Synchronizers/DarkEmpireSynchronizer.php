<?php

declare(strict_types=1);

namespace App\Console\Commands\Synchronizers;

use Illuminate\Console\Command;

/**
 * Artisan command to calculate and store trooper achievements based on their event history.
 *
 * This command aggregates event data for each trooper, such as total troops,
 * volunteer hours, and funds raised, and then updates their corresponding
 * achievements in the database.
 */
class DarkEmpireSynchronizer extends BaseSynchronizer
{
    public function __construct(Command $command)
    {
        parent::__construct($command, 'Dark Empire');
    }

    public function run(): void
    {
    }
}
