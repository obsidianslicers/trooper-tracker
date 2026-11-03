<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Console;

use App\Models\Trooper;
use Illuminate\Console\Command;
use TroopTracker\Plugins\Xenforo\Messages\GetXenforoSyncState;
use TroopTracker\Plugins\Xenforo\Messages\SyncXenforoUser;

class SyncUserCommand extends Command
{
    protected $signature = 'xenforo:sync-user
        {trooper : The ID of the trooper to sync to XenForo}
        {--dry-run : Show what would be synced without writing to XenForo}';

    protected $description = 'Synchronize one trooper to their linked XenForo user.';

    public function handle(): int
    {
        $trooper_id = (int) $this->argument('trooper');
        $trooper = $trooper_id > 0 ? Trooper::find($trooper_id) : null;

        if ($trooper === null)
        {
            $this->error($trooper_id > 0
                ? "Trooper with ID {$trooper_id} was not found."
                : 'Please provide a valid trooper ID.');

            return self::FAILURE;
        }

        if ($this->option('dry-run'))
        {
            $this->renderDryRun($trooper_id, GetXenforoSyncState::call(trooper: $trooper));

            return self::SUCCESS;
        }

        $this->info(
            "Synchronizing trooper #{$trooper->id} ({$trooper->display_name}) to XenForo..."
        );
        SyncXenforoUser::call(trooper: $trooper);
        $this->info('Synchronization complete.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $debug
     */
    private function renderDryRun(int $trooper_id, array $debug): void
    {
        $this->info("Dry-run for trooper #{$trooper_id}");
        $this->newLine();

        if ($debug['xenforo_user_id'] === null)
        {
            $this->warn('No XenForo account linked to this trooper.');

            return;
        }

        $this->line("XenForo user ID: <comment>{$debug['xenforo_user_id']}</comment>");
        $this->newLine();
        $this->table(['Field', 'Group IDs'], [
            ['All TT-managed group IDs (any org)', $this->formatIds($debug['managed_group_ids'])],
            ['Current XenForo secondary groups', $this->formatIds($debug['current_secondary'])],
            ['  ↳ TT-managed portion (current)', $this->formatIds($debug['current_tt_managed'])],
            ['  ↳ External / preserved', $this->formatIds($debug['current_preserved'])],
            ['Desired TT-managed groups', $this->formatIds($debug['desired_managed'])],
        ]);
        $this->newLine();
        $this->renderOutcome($debug);
    }

    /**
     * @param  array<string, mixed>  $debug
     */
    private function renderOutcome(array $debug): void
    {
        if (!$debug['would_send'])
        {
            $this->info('No change: TT-managed groups already match XenForo. '
                .'secondary_group_ids will NOT be sent.');

            return;
        }

        $this->warn('Change detected: secondary_group_ids WILL be sent.');
        $this->line('  Would send: '.$this->formatIds($debug['computed_result'] ?? []));
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function formatIds(array $ids): string
    {
        return $ids === [] ? '(none)' : implode(', ', $ids);
    }
}
