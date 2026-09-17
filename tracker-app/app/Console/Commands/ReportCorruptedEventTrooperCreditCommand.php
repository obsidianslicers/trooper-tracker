<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bus\MagicBus;
use App\Features\Reports\Queries\GetCorruptedEventTrooperCreditQuery;
use App\Models\EventTrooper;
use Illuminate\Console\Command;

/**
 * Diagnostic report for the admin roster-editor bug where saving the roster form could
 * silently wipe another attended trooper's costume/org credit (shown as "N/A" with no
 * troop credit). Read-only — the original values aren't recoverable, so this only
 * surfaces affected rows for manual review; it does not attempt to guess and write a
 * replacement value.
 */
class ReportCorruptedEventTrooperCreditCommand extends Command
{
    protected $signature = 'tracker:report-corrupted-event-trooper-credit
        {--trooper= : Restrict the report to a single trooper ID}';

    protected $description = 'List ATTENDED event_troopers rows whose costume/org credit has been wiped';

    public function handle(MagicBus $bus): int
    {
        $trooper_id = $this->option('trooper') !== null ? (int) $this->option('trooper') : null;

        $rows = $bus->send(new GetCorruptedEventTrooperCreditQuery($trooper_id));

        if ($rows->isEmpty())
        {
            $this->info('No corrupted event_trooper credit rows found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Event Trooper ID', 'Trooper', 'Event', 'Event Date', 'Costume ID', 'Is Handler', 'Org ID', 'Costume Org IDs'],
            $rows->map(fn (EventTrooper $event_trooper) => [
                $event_trooper->id,
                $event_trooper->trooper->display_name,
                $event_trooper->event_shift->event->name,
                $event_trooper->event_shift->event->event_start?->toDateString(),
                $event_trooper->costume_id ?? '-',
                $event_trooper->is_handler ? 'yes' : 'no',
                $event_trooper->organization_id ?? '-',
                empty($event_trooper->costume_organization_ids) ? '-' : implode(', ', $event_trooper->costume_organization_ids),
            ])->all()
        );

        $this->warn("Found {$rows->count()} corrupted row(s). Original values are not recoverable — review and re-enter credit manually.");

        return self::SUCCESS;
    }
}
