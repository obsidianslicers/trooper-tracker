<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\EventShift;
use App\Models\EventTrooper;
use Illuminate\Console\Command;

class ReopenFutureShiftsCommand extends Command
{
    protected $signature = 'tracker:reopen-future-shifts
        {--dry-run : Preview what would be changed without making any changes}';

    protected $description = 'Reopen any shifts that are marked CLOSED but have not yet occurred, and reset trooper attendance back to GOING';

    public function handle(): int
    {
        $dry_run = $this->option('dry-run');

        $shifts = EventShift::with(['event', 'event_troopers'])
            ->where(EventShift::STATUS, EventStatus::CLOSED)
            ->where(EventShift::SHIFT_ENDS_AT, '>', now())
            ->orderBy(EventShift::SHIFT_ENDS_AT)
            ->get();

        if ($shifts->isEmpty())
        {
            $this->info('No future shifts found with CLOSED status. Nothing to do.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('<fg=cyan;options=bold>── Future shifts incorrectly marked CLOSED ────────</>');

        $attendance_statuses = [EventTrooperStatus::ATTENDED, EventTrooperStatus::UNABLE_TO_ATTEND];

        foreach ($shifts as $shift)
        {
            $affected_troopers = $shift->event_troopers
                ->whereIn(EventTrooper::STATUS, $attendance_statuses);

            $this->info(sprintf(
                '  [Shift %d] %s — %s',
                $shift->id,
                $shift->event->name ?? 'Unknown Event',
                $shift->shift_ends_at->format('M j, Y g:i A'),
            ));

            if ($affected_troopers->isNotEmpty())
            {
                $this->line(sprintf(
                    '    %d trooper(s) with confirmed attendance will be reset to GOING',
                    $affected_troopers->count(),
                ));
            }
        }

        $this->newLine();

        if ($dry_run)
        {
            $this->warn('Dry run — no changes made. Remove --dry-run to apply.');

            return self::SUCCESS;
        }

        if (! $this->confirm(sprintf('Reopen %d shift(s) and reset trooper attendance?', $shifts->count())))
        {
            $this->line('Aborted.');

            return self::SUCCESS;
        }

        $shifts_reopened = 0;
        $troopers_reset = 0;

        foreach ($shifts as $shift)
        {
            $reset_count = $shift->event_troopers
                ->whereIn(EventTrooper::STATUS, $attendance_statuses)
                ->count();

            EventTrooper::where(EventTrooper::EVENT_SHIFT_ID, $shift->id)
                ->whereIn(EventTrooper::STATUS, array_map(fn ($s) => $s->value, $attendance_statuses))
                ->update([EventTrooper::STATUS => EventTrooperStatus::GOING->value]);

            $troopers_reset += $reset_count;

            $shift->status = EventStatus::OPEN;
            $shift->save();

            $shifts_reopened++;
        }

        $this->newLine();
        $this->info("Done. Reopened {$shifts_reopened} shift(s), reset {$troopers_reset} trooper attendance record(s) to GOING.");

        return self::SUCCESS;
    }
}
