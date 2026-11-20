<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Messages\Events\Commands\DeleteEventUpload;
use App\Messages\Events\Queries\GetEventUploadsMissingFiles;
use App\Models\EventUpload;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class PruneMissingUploadsCommand extends Command
{
    protected $signature = 'tracker:prune-missing-uploads
        {--delete : Delete the records (without it, only lists them)}
        {--force : Skip the confirmation prompt when deleting}';

    protected $description = 'Find event photo records whose image file no longer exists, and optionally delete them.';

    public function handle(): int
    {
        $missing = GetEventUploadsMissingFiles::call();

        if ($missing->isEmpty())
        {
            $this->info('Every event photo has its file on disk.');

            return self::SUCCESS;
        }

        $this->showMissing($missing);

        if (!$this->option('delete'))
        {
            $this->warn("{$missing->count()} photo record(s) point at missing files. Run again with --delete to remove them.");

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm("Delete {$missing->count()} photo record(s)?"))
        {
            $this->info('Nothing deleted.');

            return self::SUCCESS;
        }

        $missing->each(fn (EventUpload $upload) => DeleteEventUpload::call(event_upload: $upload));

        $this->info("Deleted {$missing->count()} photo record(s).");

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, EventUpload>  $missing
     */
    private function showMissing(Collection $missing): void
    {
        $this->table(
            ['ID', 'Event', 'Trooper', 'File'],
            $missing->map(fn (EventUpload $upload): array => [
                $upload->id,
                $upload->event_id,
                $upload->trooper_id,
                $upload->image_path_lg,
            ])->all(),
        );
    }
}
