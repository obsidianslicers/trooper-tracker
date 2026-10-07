<?php

declare(strict_types=1);

namespace Database\Seeders\Issues;

use App\Bus\MagicBus;
use App\Enums\EventTrooperStatus;
use App\Mail\Fix407OutstandingCredit;
use App\Models\EventTrooper;
use Database\Seeders\Issues\Concerns\ReportsToAdministrators;
use Database\Seeders\Issues\Support\HistoricalCreditResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Makes every TT1.0 signup's credit match the club its legacy signup recorded.
 *
 * TT1.0 data is authoritative for TT1.0 signups. A row is a TT1.0 signup when a matching legacy
 * event_sign_up record exists (via the same duplicate-shift mapping EventSeeder used) — whatever
 * the shift date, since events scheduled before the TT2.0 launch can still be in the future.
 * Over the years these rows picked up credit from current membership (old Fix406/407 backfills,
 * admin roster saves) instead of the costume club the trooper actually signed up under.
 *
 * For each attended TT1.0 row, the credited clubs become exactly the legacy costume's club(s):
 * stored region/unit ids under those clubs are kept, missing clubs are added, others dropped.
 * Rows whose legacy signup can't be mapped to a club (or whose duplicate legacy signups
 * disagree) keep their current credit and are emailed to administrators. Uncredited ones are
 * left to Fix406, which reports them itself.
 */
class Fix407 extends Seeder
{
    use ReportsToAdministrators;

    public function run(MagicBus $bus): void
    {
        $outstanding_rows = [];

        DB::transaction(function () use (&$outstanding_rows): void
        {
            $counts = ['tt1_rows' => 0, 'already_correct' => 0, 'corrected' => 0, 'outstanding' => 0];

            $resolver = HistoricalCreditResolver::load();

            if (!$resolver->legacy()->isAvailable())
            {
                $this->command?->warn('Fix407: legacy TT1.0 tables not found; nothing to reconcile.');

                return;
            }

            EventTrooper::query()
                ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
                ->with(['trooper', 'costume', 'event_shift.event'])
                ->chunkById(500, function ($event_troopers) use (&$counts, &$outstanding_rows, $resolver): void
                {
                    foreach ($event_troopers as $event_trooper)
                    {
                        if ($resolver->isTt1Signup($event_trooper))
                        {
                            $this->reconcileRow($event_trooper, $resolver, $counts, $outstanding_rows);
                        }
                    }
                });

            $this->command?->info('Fix407 complete:');
            $this->command?->info("  TT1.0 signups checked:            {$counts['tt1_rows']}");
            $this->command?->info("  Already matching legacy signup:   {$counts['already_correct']}");
            $this->command?->info("  Corrected to legacy signup:       {$counts['corrected']}");
            $this->command?->info("  Outstanding (admin review):       {$counts['outstanding']}");
        });

        $this->emailAdministrators($bus, Fix407OutstandingCredit::class, $outstanding_rows);
    }

    /**
     * @param  array<string, int>  $counts
     * @param  array<int, array<string, mixed>>  $outstanding_rows
     */
    private function reconcileRow(
        EventTrooper $event_trooper,
        HistoricalCreditResolver $resolver,
        array &$counts,
        array &$outstanding_rows,
    ): void {
        $counts['tt1_rows']++;

        $legacy = $resolver->legacyCredit($event_trooper);
        $current_ids = $event_trooper->creditedOrgIds();

        if (!$legacy->isResolved())
        {
            if (!empty($current_ids))
            {
                $counts['outstanding']++;
                $outstanding_rows[] = $this->reportRow($event_trooper, $legacy->note.' Existing credit left unchanged.');
            }

            return;
        }

        $target_ids = $resolver->mergeKeepingSpecificity($current_ids, $legacy->org_ids);

        if (HistoricalCreditResolver::sameIds($target_ids, $event_trooper->costume_organization_ids ?? []))
        {
            $counts['already_correct']++;

            return;
        }

        $event_trooper->costume_organization_ids = $target_ids;
        $event_trooper->saveQuietly();
        $counts['corrected']++;
    }
}
