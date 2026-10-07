<?php

declare(strict_types=1);

namespace Database\Seeders\Issues;

use App\Bus\MagicBus;
use App\Enums\AchievementType;
use App\Enums\EventTrooperStatus;
use App\Mail\Fix409OutstandingCredit;
use App\Models\EventTrooper;
use App\Models\TrooperAchievement;
use Database\Seeders\Issues\Concerns\ReportsToAdministrators;
use Database\Seeders\Issues\Support\CreditCheck;
use Database\Seeders\Issues\Support\HistoricalCreditResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Final consistency check: removes troop credit a shift could never have earned.
 *
 * Earlier backfills (old Fix406/407, Fix408's re-resolution) credited old shifts from the
 * trooper's *current* clubs — e.g. trooper 644 joined Rebel Legion in 2026 and had 16 shifts from
 * 2017–2021 credited to it. Every attended row with credit is checked by signup origin:
 *
 *   - TT1.0 signup: only the club(s) its legacy signup recorded are possible.
 *   - TT2.0 signup: a club is impossible if the trooper joined it after the shift, or provably
 *     left it before. A club with no membership evidence either way is kept and reported.
 *
 * Impossible credit is removed — never added. If nothing survives, the row is re-resolved from
 * the same rules Fix406 uses, or cleared and reported. Rows credited only via organization_id
 * are reported, not changed. Finally every club-scoped troop-count achievement is checked
 * against the trooper's remaining credited shifts.
 */
class Fix409 extends Seeder
{
    use ReportsToAdministrators;

    /** @var array<string, int> */
    private const array TROOP_THRESHOLDS = [
        AchievementType::FIRST_TROOP->value => 1,
        AchievementType::TROOPED_10->value => 10,
        AchievementType::TROOPED_25->value => 25,
        AchievementType::TROOPED_50->value => 50,
        AchievementType::TROOPED_75->value => 75,
        AchievementType::TROOPED_100->value => 100,
        AchievementType::TROOPED_150->value => 150,
        AchievementType::TROOPED_200->value => 200,
        AchievementType::TROOPED_250->value => 250,
        AchievementType::TROOPED_300->value => 300,
        AchievementType::TROOPED_400->value => 400,
        AchievementType::TROOPED_500->value => 500,
        AchievementType::TROOPED_501->value => 501,
    ];

    public function run(MagicBus $bus): void
    {
        $outstanding_rows = [];

        DB::transaction(function () use (&$outstanding_rows): void
        {
            $counts = [
                'rows_checked' => 0,
                'impossible_credit_removed' => 0,
                'rows_trimmed' => 0,
                'rows_reresolved' => 0,
                'rows_cleared' => 0,
                'unknown_reported' => 0,
                'achievements_removed' => 0,
            ];

            $resolver = HistoricalCreditResolver::load();

            if (!$resolver->legacy()->isAvailable())
            {
                $this->command?->warn('Fix409: legacy TT1.0 tables not found — every row is treated as a TT2.0 signup.');
            }

            EventTrooper::query()
                ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
                ->where(function ($query): void
                {
                    $query->whereJsonLength(EventTrooper::COSTUME_ORGANIZATION_IDS, '>', 0)
                        ->orWhereNotNull(EventTrooper::ORGANIZATION_ID);
                })
                ->with(['trooper', 'costume', 'event_shift.event'])
                ->chunkById(200, function ($event_troopers) use (&$counts, &$outstanding_rows, $resolver): void
                {
                    foreach ($event_troopers as $event_trooper)
                    {
                        $counts['rows_checked']++;
                        $this->checkRow($event_trooper, $resolver, $counts, $outstanding_rows);
                    }
                });

            $this->removeUnjustifiedAchievements($resolver, $counts);

            $this->printSummary($counts);
        });

        $this->emailAdministrators($bus, Fix409OutstandingCredit::class, $outstanding_rows);
    }

    /**
     * @param  array<string, int>  $counts
     * @param  array<int, array<string, mixed>>  $outstanding_rows
     */
    private function checkRow(
        EventTrooper $event_trooper,
        HistoricalCreditResolver $resolver,
        array &$counts,
        array &$outstanding_rows,
    ): void {
        if (empty($event_trooper->costume_organization_ids))
        {
            $this->checkOrganizationIdOnlyRow($event_trooper, $resolver, $counts, $outstanding_rows);

            return;
        }

        $check = $resolver->checkStoredCredit($event_trooper);

        if (!empty($check->unknown_root_ids))
        {
            $counts['unknown_reported']++;
            $outstanding_rows[] = $this->reportRow($event_trooper, $this->unknownNote($check, $resolver));
        }

        if (empty($check->remove_ids))
        {
            return;
        }

        $counts['impossible_credit_removed'] += count($check->remove_ids);

        if (!empty($check->keep_ids))
        {
            $event_trooper->costume_organization_ids = $check->keep_ids;
            $event_trooper->saveQuietly();
            $counts['rows_trimmed']++;

            return;
        }

        $this->reresolveRow($event_trooper, $check, $resolver, $counts, $outstanding_rows);
    }

    /**
     * @param  array<string, int>  $counts
     * @param  array<int, array<string, mixed>>  $outstanding_rows
     */
    private function reresolveRow(
        EventTrooper $event_trooper,
        CreditCheck $check,
        HistoricalCreditResolver $resolver,
        array &$counts,
        array &$outstanding_rows,
    ): void {
        $resolution = $resolver->expectedCredit($event_trooper);

        if ($resolution->resolved)
        {
            $event_trooper->costume_organization_ids = $resolution->org_ids;
            $event_trooper->saveQuietly();
            $counts['rows_reresolved']++;

            return;
        }

        $event_trooper->costume_organization_ids = [];
        $event_trooper->saveQuietly();
        $counts['rows_cleared']++;
        $outstanding_rows[] = $this->reportRow(
            $event_trooper,
            'Removed impossible credit ('.$resolver->namesOf($check->remove_ids).'). '.$resolution->reason,
        );
    }

    /**
     * organization_id is the club chosen at signup; it isn't rewritten here, only reported.
     *
     * @param  array<string, int>  $counts
     * @param  array<int, array<string, mixed>>  $outstanding_rows
     */
    private function checkOrganizationIdOnlyRow(
        EventTrooper $event_trooper,
        HistoricalCreditResolver $resolver,
        array &$counts,
        array &$outstanding_rows,
    ): void {
        $check = $resolver->checkStoredCredit($event_trooper, [(int) $event_trooper->organization_id]);

        if (empty($check->remove_ids) && empty($check->unknown_root_ids))
        {
            return;
        }

        $counts['unknown_reported']++;
        $outstanding_rows[] = $this->reportRow(
            $event_trooper,
            empty($check->remove_ids)
                ? $this->unknownNote($check, $resolver).' (credited via organization_id)'
                : 'Credited via organization_id to '.$resolver->namesOf($check->remove_ids)
                    .", which this shift couldn't have earned. Not changed automatically.",
        );
    }

    private function unknownNote(CreditCheck $check, HistoricalCreditResolver $resolver): string
    {
        return 'No membership evidence for '.$resolver->namesOf($check->unknown_root_ids)
            .' on the shift date; credit kept.';
    }

    /**
     * Checks every club-scoped troop-count achievement against the trooper's current credited
     * shifts — not just troopers this run touched, since credit can be lost upstream (Fix407,
     * Fix408) without this run changing that trooper's rows.
     *
     * Hard-deletes, not soft-deletes: the table's unique index doesn't exclude soft-deleted rows,
     * and the recalculation command doesn't query withTrashed(), so a soft-deleted row would
     * permanently block any future legitimate milestone for that trooper/type/club.
     *
     * @param  array<string, int>  $counts
     */
    private function removeUnjustifiedAchievements(HistoricalCreditResolver $resolver, array &$counts): void
    {
        $credited_counts = $this->countCreditedRowsByRoot($resolver);

        TrooperAchievement::query()
            ->whereNotNull(TrooperAchievement::ORGANIZATION_ID)
            ->whereIn(TrooperAchievement::TYPE, array_keys(self::TROOP_THRESHOLDS))
            ->chunkById(500, function ($achievements) use ($credited_counts, &$counts): void
            {
                foreach ($achievements as $achievement)
                {
                    $threshold = self::TROOP_THRESHOLDS[$achievement->type->value];
                    $current_count = $credited_counts[$achievement->trooper_id][$achievement->organization_id] ?? 0;

                    if ($current_count < $threshold)
                    {
                        $achievement->forceDelete();
                        $counts['achievements_removed']++;
                    }
                }
            });
    }

    /** @return array<int, array<int, int>> [trooper_id][root_id] => credited attended rows */
    private function countCreditedRowsByRoot(HistoricalCreditResolver $resolver): array
    {
        $credited = [];

        EventTrooper::query()
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
            ->whereJsonLength(EventTrooper::COSTUME_ORGANIZATION_IDS, '>', 0)
            ->select([EventTrooper::ID, EventTrooper::TROOPER_ID, EventTrooper::COSTUME_ORGANIZATION_IDS])
            ->chunkById(1000, function ($event_troopers) use (&$credited, $resolver): void
            {
                foreach ($event_troopers as $event_trooper)
                {
                    foreach ($resolver->rootsOf($event_trooper->costume_organization_ids) as $root_id)
                    {
                        $credited[$event_trooper->trooper_id][$root_id] = ($credited[$event_trooper->trooper_id][$root_id] ?? 0) + 1;
                    }
                }
            });

        return $credited;
    }

    /** @param  array<string, int>  $counts */
    private function printSummary(array $counts): void
    {
        $this->command?->info('Fix409 complete:');
        $this->command?->info("  Credited rows checked:            {$counts['rows_checked']}");
        $this->command?->info("  Impossible credits removed:       {$counts['impossible_credit_removed']}");
        $this->command?->info("  Rows trimmed (credit kept):       {$counts['rows_trimmed']}");
        $this->command?->info("  Rows re-resolved:                 {$counts['rows_reresolved']}");
        $this->command?->info("  Rows cleared (admin review):      {$counts['rows_cleared']}");
        $this->command?->info("  Unproven credit kept (reported):  {$counts['unknown_reported']}");
        $this->command?->info("  Achievement rows removed:         {$counts['achievements_removed']}");
        $this->command?->newLine();
        $this->command?->info('  Run `php artisan tracker:calculate-trooper-achievements` next so any');
        $this->command?->info('  legitimately-earned club milestones are created fresh.');
    }
}
