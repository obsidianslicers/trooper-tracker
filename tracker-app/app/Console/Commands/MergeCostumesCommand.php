<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Messages\Costumes\Commands\Merge\MergeCostumes;
use App\Messages\Costumes\Queries\GetCostumeUsage;
use App\Models\Costume;
use Illuminate\Console\Command;

class MergeCostumesCommand extends Command
{
    protected $signature = 'tracker:merge-costumes
        {source_costume_id : ID of the duplicate costume to fold in and delete}
        {target_costume_id : ID of the costume to keep}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Merge a duplicate costume into another, moving every trooper, club, and event sign-up to the kept costume.';

    public function handle(): int
    {
        $source_id = (int) $this->argument('source_costume_id');
        $target_id = (int) $this->argument('target_costume_id');

        if ($source_id === $target_id)
        {
            $this->error('source_costume_id and target_costume_id must be different costumes.');

            return self::FAILURE;
        }

        $source = Costume::withTrashed()->find($source_id);
        $target = Costume::find($target_id);

        if ($source === null || $target === null)
        {
            $this->error($source === null
                ? "Source costume {$source_id} was not found."
                : "Target costume {$target_id} was not found.");

            return self::FAILURE;
        }

        if ($source->countsAsHandler())
        {
            $this->error("The {$source->name} costume cannot be merged away.");

            return self::FAILURE;
        }

        $this->showUsage($source, $target);

        if (!$this->option('force') && !$this->confirm($this->confirmationQuestion($source, $target)))
        {
            $this->info('Merge cancelled.');

            return self::SUCCESS;
        }

        MergeCostumes::call(target_costume: $target, source_costume: $source);

        $this->info("Merged \"{$source->name}\" into \"{$target->name}\".");

        return self::SUCCESS;
    }

    private function showUsage(Costume $source, Costume $target): void
    {
        $this->table(
            ['', 'ID', 'Name', 'Clubs', 'Troopers', 'Event sign-ups'],
            [
                ['Source (deleted)', ...$this->usageRow($source)],
                ['Target (kept)', ...$this->usageRow($target)],
            ],
        );
    }

    /**
     * @return array<int, int|string>
     */
    private function usageRow(Costume $costume): array
    {
        $usage = GetCostumeUsage::call(costume: $costume);

        return [
            $costume->id,
            $costume->name,
            $usage['organizations'],
            $usage['troopers'],
            $usage['event_signups'],
        ];
    }

    private function confirmationQuestion(Costume $source, Costume $target): string
    {
        return "Merge \"{$source->name}\" into \"{$target->name}\"? "
            ."\"{$source->name}\" will be permanently deleted.";
    }
}
