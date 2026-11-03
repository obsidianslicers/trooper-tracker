<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Discord\Console;

use Illuminate\Console\Command;
use TroopTracker\Plugins\Discord\Messages\VerifyMentionMigration;

class VerifyMentionsCommand extends Command
{
    protected $signature = 'discord:verify-mentions';

    protected $description = 'Check that every Discord mention was copied into the plugin.';

    public function handle(): int
    {
        $report = VerifyMentionMigration::call();

        $this->info(
            "Checked {$report['checked']} organizations with a Discord mention in the old column."
        );

        if ($report['changed'] !== [])
        {
            $this->line(count($report['changed']).' were edited after the copy (expected):');
            $this->table(['ID', 'Organization', 'Old', 'New'], $report['changed']);
        }

        if ($report['missing'] !== [] || $report['mismatched'] !== [])
        {
            $this->reportProblems($report);

            return self::FAILURE;
        }

        $this->info(
            'Every mention is in the Discord plugin. The old column can be dropped safely.'
        );

        return self::SUCCESS;
    }

    private function reportProblems(array $report): void
    {
        if ($report['missing'] !== [])
        {
            $this->error(count($report['missing']).' are missing from the Discord plugin.');
            $this->table(['ID', 'Organization', 'Old'], $report['missing']);
        }

        if ($report['mismatched'] !== [])
        {
            $this->error(count($report['mismatched']).' were copied with a different value.');
            $this->table(['ID', 'Organization', 'Old', 'Copied'], $report['mismatched']);
        }

        $this->error('Do not drop tt_organizations.discord_mention yet.');
    }
}
