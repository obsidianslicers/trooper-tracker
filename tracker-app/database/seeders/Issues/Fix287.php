<?php

declare(strict_types=1);

namespace Database\Seeders\Issues;

use App\Models\Costume;
use App\Models\EventTrooper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Repairs costume_organization_ids on event_trooper records corrupted by the costume-change bug.
 *
 * When a trooper updated their costume via the HTMX dropdown, only costume_id was saved —
 * costume_organization_ids was never recalculated. This left stale org IDs from the previous
 * costume, causing incorrect club credit on service records.
 *
 * Two cases are repaired:
 *   A. costume_id set, costume_organization_ids IS NULL, and approvedOrgIdsForTrooper returns
 *      non-empty → populate from costume approvals.
 *   B. costume_organization_ids contains IDs not approved for that trooper+costume combo →
 *      filter to the valid intersection; if the intersection is empty, replace entirely with
 *      the approved IDs.
 *
 * Handler and Command Staff costumes are skipped — their credit derives from membership, not
 * costume approvals, and is resolved correctly at attendance time.
 *
 * Records where approvedOrgIdsForTrooper returns empty are skipped (cannot determine the
 * correct org — this may indicate a removed costume approval and requires manual review).
 */
class Fix287 extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void
        {
            $counts = [
                'scanned' => 0,
                'null_populated' => 0,
                'stale_corrected' => 0,
                'skipped_no_approval' => 0,
                'skipped_handler' => 0,
            ];

            EventTrooper::query()
                ->whereNotNull(EventTrooper::COSTUME_ID)
                ->with(['costume'])
                ->chunk(200, function ($event_troopers) use (&$counts): void
                {
                    foreach ($event_troopers as $event_trooper)
                    {
                        $counts['scanned']++;

                        $costume = $event_trooper->costume;

                        if ($costume === null || $costume->countsAsHandler())
                        {
                            $counts['skipped_handler']++;
                            continue;
                        }

                        $correct_ids = $costume->approvedOrgIdsForTrooper($event_trooper->trooper_id);

                        if (empty($correct_ids))
                        {
                            $counts['skipped_no_approval']++;
                            continue;
                        }

                        $current_ids = $event_trooper->costume_organization_ids ?? [];

                        if (empty($current_ids))
                        {
                            $event_trooper->costume_organization_ids = $correct_ids;
                            $event_trooper->saveQuietly();
                            $counts['null_populated']++;
                            continue;
                        }

                        $invalid_ids = array_diff($current_ids, $correct_ids);

                        if (!empty($invalid_ids))
                        {
                            $intersection = array_values(array_intersect($current_ids, $correct_ids));
                            $event_trooper->costume_organization_ids = !empty($intersection) ? $intersection : $correct_ids;
                            $event_trooper->saveQuietly();
                            $counts['stale_corrected']++;
                        }
                    }
                });

            $this->command?->info('Fix287 complete:');
            $this->command?->info("  Scanned:                      {$counts['scanned']}");
            $this->command?->info("  Null org IDs populated:       {$counts['null_populated']}");
            $this->command?->info("  Stale org IDs corrected:      {$counts['stale_corrected']}");
            $this->command?->info("  Skipped (handler/staff):      {$counts['skipped_handler']}");
            $this->command?->info("  Skipped (no costume approval): {$counts['skipped_no_approval']}");
        });
    }
}
