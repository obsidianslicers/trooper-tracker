<?php

declare(strict_types=1);

namespace Database\Seeders\Issues;

use App\Bus\MagicBus;
use App\Enums\EventTrooperStatus;
use App\Mail\Fix406OutstandingCredit;
use App\Models\EventTrooper;
use Database\Seeders\Issues\Concerns\ReportsToAdministrators;
use Database\Seeders\Issues\Support\HistoricalCreditResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Restores credit on attended EventTrooper rows left with no credit source at all.
 *
 * Before the admin roster-update controller was fixed (#262), every save cleared both
 * costume_organization_ids and organization_id whenever org-selection data wasn't submitted, and
 * the original import never credited handler-role troopers. event_trooper only audits status, so
 * the lost values can't be read back — they're re-derived by HistoricalCreditResolver instead:
 *
 *   - TT1.0 signup: the legacy signup's own costume club, nothing else.
 *   - TT2.0 signup: the costume's clubs intersected with the trooper's memberships on the shift
 *     date (every such club for a handler / no costume). Never a club joined later.
 *
 * Anything the evidence can't settle is left uncredited and emailed to administrators.
 */
class Fix406 extends Seeder
{
    use ReportsToAdministrators;

    public function run(MagicBus $bus): void
    {
        $outstanding_rows = [];

        DB::transaction(function () use (&$outstanding_rows): void
        {
            $counts = ['scanned' => 0, 'resolved_tt1' => 0, 'resolved_tt2' => 0, 'outstanding' => 0];

            $resolver = HistoricalCreditResolver::load();
            $this->warnIfLegacyUnavailable($resolver);

            EventTrooper::query()
                ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
                ->whereNull(EventTrooper::ORGANIZATION_ID)
                ->where(function ($query): void
                {
                    // whereJsonLength, not orWhere('[]') — MySQL never does JSON-aware equality
                    // against a bound parameter, so the old orWhere('[]') matched nothing
                    $query->whereNull(EventTrooper::COSTUME_ORGANIZATION_IDS)
                        ->orWhereJsonLength(EventTrooper::COSTUME_ORGANIZATION_IDS, 0);
                })
                ->with(['trooper', 'costume', 'event_shift.event'])
                // chunkById (not chunk): resolved rows stop matching the filter mid-iteration
                ->chunkById(200, function ($event_troopers) use (&$counts, &$outstanding_rows, $resolver): void
                {
                    foreach ($event_troopers as $event_trooper)
                    {
                        $this->restoreRow($event_trooper, $resolver, $counts, $outstanding_rows);
                    }
                });

            $this->command?->info('Fix406 complete:');
            $this->command?->info("  Scanned (no credit):              {$counts['scanned']}");
            $this->command?->info("  Restored from TT1.0 signup:       {$counts['resolved_tt1']}");
            $this->command?->info("  Restored from TT2.0 history:      {$counts['resolved_tt2']}");
            $this->command?->info("  Outstanding (admin review):       {$counts['outstanding']}");
        });

        $this->emailAdministrators($bus, Fix406OutstandingCredit::class, $outstanding_rows);
    }

    /**
     * @param  array<string, int>  $counts
     * @param  array<int, array<string, mixed>>  $outstanding_rows
     */
    private function restoreRow(
        EventTrooper $event_trooper,
        HistoricalCreditResolver $resolver,
        array &$counts,
        array &$outstanding_rows,
    ): void {
        $counts['scanned']++;

        $resolution = $resolver->expectedCredit($event_trooper);

        if (!$resolution->resolved)
        {
            $counts['outstanding']++;
            $outstanding_rows[] = $this->reportRow($event_trooper, $resolution->reason);

            return;
        }

        $event_trooper->costume_organization_ids = $resolution->org_ids;
        $event_trooper->saveQuietly();

        $counts[$resolver->isTt1Signup($event_trooper) ? 'resolved_tt1' : 'resolved_tt2']++;
    }

    private function warnIfLegacyUnavailable(HistoricalCreditResolver $resolver): void
    {
        if (!$resolver->legacy()->isAvailable())
        {
            $this->command?->warn('Fix406: legacy TT1.0 tables not found — every row is treated as a TT2.0 signup.');
        }
    }
}
