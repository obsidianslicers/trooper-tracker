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
 * Final consistency check: removes troop credit a shift could never have earned (a club the
 * TT1.0 signup didn't record, or one joined after the shift) and the club milestones that no
 * longer meet their threshold. Only ever removes credit. See docs/ISSUE_MIGRATIONS.md.
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

    private HistoricalCreditResolver $resolver;

    /** @var array<string, int> */
    private array $counts = [];

    /** @var array<int, array<string, mixed>> */
    private array $outstanding_rows = [];

    public function run(MagicBus $bus): void
    {
        $this->counts = array_fill_keys([
            'rows_checked', 'impossible_credit_removed', 'rows_trimmed', 'rows_reresolved',
            'rows_cleared', 'unknown_reported', 'achievements_removed',
        ], 0);
        $this->outstanding_rows = [];

        DB::transaction(function (): void
        {
            $this->resolver = HistoricalCreditResolver::load();
            $this->warnIfLegacyUnavailable();

            $this->checkCreditedRows();
            $this->removeUnjustifiedAchievements();

            $this->printSummary();
        });

        $this->emailAdministrators($bus, Fix409OutstandingCredit::class, $this->outstanding_rows);
    }

    private function checkCreditedRows(): void
    {
        EventTrooper::query()
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
            ->where(function ($query): void
            {
                $query->whereJsonLength(EventTrooper::COSTUME_ORGANIZATION_IDS, '>', 0)
                    ->orWhereNotNull(EventTrooper::ORGANIZATION_ID);
            })
            ->with(['trooper', 'costume', 'event_shift.event'])
            ->chunkById(200, function ($event_troopers): void
            {
                foreach ($event_troopers as $event_trooper)
                {
                    $this->counts['rows_checked']++;
                    $this->checkRow($event_trooper);
                }
            });
    }

    private function checkRow(EventTrooper $event_trooper): void
    {
        if (empty($event_trooper->costume_organization_ids))
        {
            $this->checkOrganizationIdOnlyRow($event_trooper);

            return;
        }

        $check = $this->resolver->checkStoredCredit($event_trooper);

        if (!empty($check->unknown_root_ids))
        {
            $this->counts['unknown_reported']++;
            $reason = $this->unknownNote($check);
            $this->outstanding_rows[] = $this->reportRow($event_trooper, $reason);
        }

        if (!empty($check->remove_ids))
        {
            $this->counts['impossible_credit_removed'] += count($check->remove_ids);
            $this->removeImpossibleCredit($event_trooper, $check);
        }
    }

    private function removeImpossibleCredit(EventTrooper $event_trooper, CreditCheck $check): void
    {
        if (!empty($check->keep_ids))
        {
            $event_trooper->costume_organization_ids = $check->keep_ids;
            $event_trooper->saveQuietly();
            $this->counts['rows_trimmed']++;

            return;
        }

        $resolution = $this->resolver->expectedCredit($event_trooper);

        if ($resolution->resolved)
        {
            $event_trooper->costume_organization_ids = $resolution->org_ids;
            $event_trooper->saveQuietly();
            $this->counts['rows_reresolved']++;

            return;
        }

        $event_trooper->costume_organization_ids = [];
        $event_trooper->saveQuietly();
        $this->counts['rows_cleared']++;

        $removed = $this->resolver->namesOf($check->remove_ids);
        $reason = "Removed impossible credit ({$removed}). {$resolution->reason}";
        $this->outstanding_rows[] = $this->reportRow($event_trooper, $reason);
    }

    /** organization_id is the club chosen at signup; it isn't rewritten here, only reported. */
    private function checkOrganizationIdOnlyRow(EventTrooper $event_trooper): void
    {
        $organization_ids = [(int) $event_trooper->organization_id];
        $check = $this->resolver->checkStoredCredit($event_trooper, $organization_ids);

        if (empty($check->remove_ids) && empty($check->unknown_root_ids))
        {
            return;
        }

        $reason = empty($check->remove_ids)
            ? $this->unknownNote($check).' (credited via organization_id)'
            : 'Credited via organization_id to '.$this->resolver->namesOf($check->remove_ids)
                .", which this shift couldn't have earned. Not changed automatically.";

        $this->counts['unknown_reported']++;
        $this->outstanding_rows[] = $this->reportRow($event_trooper, $reason);
    }

    private function unknownNote(CreditCheck $check): string
    {
        return 'No membership evidence for '.$this->resolver->namesOf($check->unknown_root_ids)
            .' on the shift date; credit kept.';
    }

    /**
     * Checks every club milestone, not just troopers this run touched — credit can be lost
     * upstream (Fix407, Fix408) without this run changing that trooper's rows.
     *
     * Hard-deletes: the table's unique index includes soft-deleted rows, so a soft-deleted
     * milestone would block the recalculation from ever recreating it.
     */
    private function removeUnjustifiedAchievements(): void
    {
        $credited_counts = $this->countCreditedRowsByRoot();

        TrooperAchievement::query()
            ->whereNotNull(TrooperAchievement::ORGANIZATION_ID)
            ->whereIn(TrooperAchievement::TYPE, array_keys(self::TROOP_THRESHOLDS))
            ->chunkById(500, function ($achievements) use ($credited_counts): void
            {
                foreach ($achievements as $achievement)
                {
                    $threshold = self::TROOP_THRESHOLDS[$achievement->type->value];
                    $by_root = $credited_counts[$achievement->trooper_id] ?? [];

                    if (($by_root[$achievement->organization_id] ?? 0) < $threshold)
                    {
                        $achievement->forceDelete();
                        $this->counts['achievements_removed']++;
                    }
                }
            });
    }

    /**
     * Counts the same way RecalculateTrooperRankCommandHandler does (creditedOrgIds(): costume
     * ids, else organization_id) so a milestone removed here is never recreated by it.
     *
     * @return array<int, array<int, int>> [trooper_id][root_id] => credited attended rows
     */
    private function countCreditedRowsByRoot(): array
    {
        $credited = [];

        EventTrooper::query()
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
            ->select([
                EventTrooper::ID,
                EventTrooper::TROOPER_ID,
                EventTrooper::ORGANIZATION_ID,
                EventTrooper::COSTUME_ORGANIZATION_IDS,
            ])
            ->chunkById(1000, function ($event_troopers) use (&$credited): void
            {
                foreach ($event_troopers as $event_trooper)
                {
                    $trooper_id = $event_trooper->trooper_id;

                    foreach ($this->resolver->rootsOf($event_trooper->creditedOrgIds()) as $root_id)
                    {
                        $credited[$trooper_id][$root_id] ??= 0;
                        $credited[$trooper_id][$root_id]++;
                    }
                }
            });

        return $credited;
    }

    private function warnIfLegacyUnavailable(): void
    {
        if (!$this->resolver->legacy()->isAvailable())
        {
            $this->command?->warn('Fix409: no legacy TT1.0 tables — treating every row as TT2.0.');
        }
    }

    private function printSummary(): void
    {
        $this->printCounts('Fix409 complete:', [
            'rows_checked' => 'Credited rows checked',
            'impossible_credit_removed' => 'Impossible credits removed',
            'rows_trimmed' => 'Rows trimmed (credit kept)',
            'rows_reresolved' => 'Rows re-resolved',
            'rows_cleared' => 'Rows cleared (admin review)',
            'unknown_reported' => 'Unproven credit kept (reported)',
            'achievements_removed' => 'Achievement rows removed',
        ], $this->counts);
        $this->printRecalculateHint();
    }
}
