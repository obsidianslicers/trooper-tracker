<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Trooper;
use App\Services\Forums\XenforoUserSyncService;
use Illuminate\Console\Command;

class SynchronizeXenforoUser extends Command
{
    protected $signature = 'tracker:synchronize-xenforo-user
                            {trooper : The ID of the trooper to sync to XenForo}
                            {--dry-run : Show what would be synced without writing to XenForo}';

    protected $description = 'Synchronize a single TroopTracker trooper to their linked XenForo user.';

    public function __construct(private readonly XenforoUserSyncService $syncService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $trooperId = (int) $this->argument('trooper');

        if ($trooperId <= 0)
        {
            $this->error('Please provide a valid trooper ID.');

            return;
        }

        $trooper = Trooper::find($trooperId);

        if (! $trooper)
        {
            $this->error("Trooper with ID {$trooperId} was not found.");

            return;
        }

        if ($this->option('dry-run'))
        {
            $this->runDryRun($trooper);

            return;
        }

        $this->info("Synchronizing trooper #{$trooper->id} ({$trooper->display_name}) to XenForo...");

        $this->syncService->syncTrooper($trooper);

        $this->info('Synchronization complete.');
    }

    private function runDryRun(Trooper $trooper): void
    {
        $this->info("Dry-run for trooper #{$trooper->id} — {$trooper->display_name}");
        $this->newLine();

        $debug = $this->syncService->debugSync($trooper);

        if ($debug['xenforo_user_id'] === null)
        {
            $this->warn('No XenForo account linked to this trooper.');

            return;
        }

        $this->line("XenForo user ID: <comment>{$debug['xenforo_user_id']}</comment>");
        $this->newLine();

        $this->table(
            ['Field', 'Group IDs'],
            [
                ['All TT-managed group IDs (any org)', $this->formatIds($debug['managed_group_ids'])],
                ['Current XenForo secondary groups',   $this->formatIds($debug['current_secondary'])],
                ['  ↳ TT-managed portion (current)',   $this->formatIds($debug['current_tt_managed'])],
                ['  ↳ External / preserved',           $this->formatIds($debug['current_preserved'])],
                ['Desired TT-managed groups',          $this->formatIds($debug['desired_managed'])],
            ]
        );

        $this->newLine();

        if (! $debug['would_send'])
        {
            $this->info('No change — TT-managed groups already match XenForo. secondary_group_ids will NOT be sent.');
        }
        else
        {
            $this->warn('Change detected — secondary_group_ids WILL be sent.');
            $this->line('  Would send: '.$this->formatIds($debug['computed_result'] ?? []));
        }
    }

    /** @param int[] $ids */
    private function formatIds(array $ids): string
    {
        return empty($ids) ? '(none)' : implode(', ', $ids);
    }
}
