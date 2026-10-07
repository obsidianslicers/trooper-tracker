<?php

declare(strict_types=1);

namespace Database\Seeders\Issues;

use App\Bus\MagicBus;
use App\Enums\AchievementType;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Mail\Fix409OutstandingCredit;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\TrooperAchievement;
use Carbon\Carbon;
use Database\Seeders\FloridaGarrison\Traits\HasClubMaps;
use Database\Seeders\Issues\Concerns\ExcludesPrematureCredit;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Stops crediting a club for a shift that happened before the trooper actually joined it.
 *
 * Fix406/Fix407 (and Fix408's re-resolution step) backfill credit for old, uncredited ATTENDED
 * shifts using the trooper's current club eligibility, with no awareness of when they actually
 * joined that club. A trooper who joined a new club recently got every old uncredited shift
 * backfilled to that club too, since the backfill only checks current membership, never
 * membership at the time of the shift.
 */
class Fix409 extends Seeder
{
    use ExcludesPrematureCredit;
    use HasClubMaps;

    /** @var array<int, int> */
    private const TROOP_THRESHOLDS = [
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
                'credit_rows_scanned' => 0,
                'premature_credit_removed' => 0,
                'corrected_partial_credit' => 0,
                'recovered_live' => 0,
                'recovered_legacy' => 0,
                'skipped_no_eligible_org' => 0,
                'achievements_removed' => 0,
            ];

            $all_orgs = Organization::all([Organization::ID, Organization::NODE_PATH])->keyBy(Organization::ID);
            $join_signals = $this->buildJoinSignals($all_orgs);
            $costume_club_map = $this->buildCostumeClubMap();

            EventTrooper::query()
                ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
                ->whereJsonLength(EventTrooper::COSTUME_ORGANIZATION_IDS, '>', 0)
                ->with(['trooper.trooper_costumes.organization_costume', 'trooper.trooper_assignments', 'costume', 'event_shift.event'])
                ->chunkById(200, function ($event_troopers) use (
                    &$outstanding_rows,
                    &$counts,
                    $all_orgs,
                    $join_signals,
                    $costume_club_map,
                ): void {
                    foreach ($event_troopers as $event_trooper)
                    {
                        $this->correctRow(
                            $event_trooper,
                            $all_orgs,
                            $join_signals,
                            $costume_club_map,
                            $counts,
                            $outstanding_rows,
                        );
                    }
                });

            $this->removeUnjustifiedAchievements($all_orgs, $counts);

            $this->printSummary($counts);
        });

        $this->emailOutstandingCredit($bus, $outstanding_rows);
    }

    /**
     * @param  Collection<int, Organization>  $all_orgs
     * @param  array<int, array<int, Carbon>>  $join_signals
     * @param  Collection<int, array{id: int, costume_club_id: int}>  $costume_club_map
     * @param  array<string, int>  $counts
     * @param  array<int, array<string, mixed>>  $outstanding_rows
     */
    private function correctRow(
        EventTrooper $event_trooper,
        Collection $all_orgs,
        array $join_signals,
        Collection $costume_club_map,
        array &$counts,
        array &$outstanding_rows,
    ): void {
        $shift_date = $event_trooper->event_shift?->shift_starts_at;

        if ($shift_date === null)
        {
            return;
        }

        $original_ids = $event_trooper->costume_organization_ids;
        $remaining_ids = $this->excludePremature($original_ids, $event_trooper->trooper_id, $shift_date, $all_orgs, $join_signals);

        if (count($remaining_ids) === count($original_ids))
        {
            // Nothing on this row predates the trooper's join date — not our row to touch.
            return;
        }

        $counts['credit_rows_scanned']++;
        $counts['premature_credit_removed'] += count($original_ids) - count($remaining_ids);

        $this->reresolveCredit($event_trooper, $remaining_ids, $shift_date, $all_orgs, $join_signals, $costume_club_map, $counts, $outstanding_rows);
    }

    /**
     * @param  array<int, int>  $remaining_ids
     * @param  Collection<int, Organization>  $all_orgs
     * @param  array<int, array<int, Carbon>>  $join_signals
     * @param  Collection<int, array{id: int, costume_club_id: int}>  $costume_club_map
     * @param  array<string, int>  $counts
     * @param  array<int, array<string, mixed>>  $outstanding_rows
     */
    private function reresolveCredit(
        EventTrooper $event_trooper,
        array $remaining_ids,
        Carbon $shift_date,
        Collection $all_orgs,
        array $join_signals,
        Collection $costume_club_map,
        array &$counts,
        array &$outstanding_rows,
    ): void {
        $live_org_ids = $this->excludePremature(
            $event_trooper->getEligibleCreditOrganizations()->pluck('id')->values()->all(),
            $event_trooper->trooper_id,
            $shift_date,
            $all_orgs,
            $join_signals,
        );

        $merged_ids = collect($remaining_ids)->merge($live_org_ids)->unique()->values()->all();

        if (!empty($merged_ids))
        {
            if ($this->sameIds($merged_ids, $event_trooper->costume_organization_ids))
            {
                return;
            }

            $event_trooper->costume_organization_ids = $merged_ids;
            $event_trooper->saveQuietly();
            $counts[empty($remaining_ids) ? 'recovered_live' : 'corrected_partial_credit']++;

            return;
        }

        $legacy_org_ids = $this->excludePremature(
            $this->resolveLegacyOrgIds($event_trooper, $costume_club_map),
            $event_trooper->trooper_id,
            $shift_date,
            $all_orgs,
            $join_signals,
        );

        if (!empty($legacy_org_ids))
        {
            if ($this->sameIds($legacy_org_ids, $event_trooper->costume_organization_ids))
            {
                return;
            }

            $event_trooper->costume_organization_ids = $legacy_org_ids;
            $event_trooper->saveQuietly();
            $counts['recovered_legacy']++;

            return;
        }

        if (empty($event_trooper->costume_organization_ids))
        {
            return;
        }

        $event_trooper->costume_organization_ids = [];
        $event_trooper->saveQuietly();

        $counts['skipped_no_eligible_org']++;
        $outstanding_rows[] = [
            'event_trooper_id' => $event_trooper->id,
            'trooper_name' => $event_trooper->trooper->display_name,
            'event_name' => $event_trooper->event_shift->event->name,
            'event_id' => $event_trooper->event_shift->event->id,
            'costume_name' => $event_trooper->costume?->name,
        ];
    }

    /** @param  array<int, int>  $a @param  array<int, int>  $b */
    private function sameIds(array $a, array $b): bool
    {
        $normalize = fn (array $ids) => collect($ids)->unique()->sort()->values()->all();

        return $normalize($a) === $normalize($b);
    }

    /** @return Collection<int, array{id: int, costume_club_id: int}> */
    private function buildCostumeClubMap(): Collection
    {
        if (!Schema::hasTable('event_sign_up') || !Schema::hasTable('costumes'))
        {
            return collect();
        }

        try
        {
            return collect($this->getCostumeClubMap())->keyBy('costume_club_id');
        }
        catch (Exception)
        {
            return collect();
        }
    }

    /**
     * @param  Collection<int, array{id: int, costume_club_id: int}>  $costume_club_map
     * @return array<int, int>
     */
    private function resolveLegacyOrgIds(EventTrooper $event_trooper, Collection $costume_club_map): array
    {
        if ($costume_club_map->isEmpty())
        {
            return [];
        }

        $legacy_signup = DB::table('event_sign_up')
            ->join('costumes', 'costumes.id', '=', 'event_sign_up.costume')
            ->where('event_sign_up.troopid', $event_trooper->event_shift_id)
            ->where('event_sign_up.trooperid', $event_trooper->trooper_id)
            ->whereNotNull('costumes.club')
            ->selectRaw('costumes.club')
            ->first();

        if ($legacy_signup === null)
        {
            return [];
        }

        $club_ids = $this->expandDualClubIds([(int) $legacy_signup->club]);

        return collect($club_ids)
            ->map(fn (int $club_id) => $costume_club_map->get($club_id)['id'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Checks every club-scoped achievement in the system against the trooper's actual current
     * credited-shift count and hard-deletes any that no longer meet their troop-count threshold.
     *
     * This runs site-wide rather than only against rows this seeder just modified. Credit a
     * trooper lost upstream of this seeder (a corrected false membership in Fix408, a legacy
     * fallback resolved differently in Fix406/407) can leave an achievement stale without this
     * run ever touching that trooper's row directly, so a narrower "only check what I just
     * changed" pass misses those. Scanning every achievement against today's authoritative
     * count is the only way to guarantee none are left over-crediting.
     *
     * Hard-deletes, not soft-deletes: the table's unique index doesn't exclude soft-deleted rows,
     * and the recalculation command doesn't query withTrashed(), so a soft-deleted row would
     * permanently block any future legitimate milestone for that trooper/type/club.
     *
     * @param  Collection<int, Organization>  $all_orgs
     * @param  array<string, int>  $counts
     */
    private function removeUnjustifiedAchievements(Collection $all_orgs, array &$counts): void
    {
        $valid_types = array_keys(self::TROOP_THRESHOLDS);

        TrooperAchievement::query()
            ->whereNotNull(TrooperAchievement::ORGANIZATION_ID)
            ->whereIn(TrooperAchievement::TYPE, $valid_types)
            ->chunkById(500, function ($achievements) use ($all_orgs, &$counts): void
            {
                foreach ($achievements as $achievement)
                {
                    $threshold = self::TROOP_THRESHOLDS[$achievement->type->value] ?? null;

                    if ($threshold === null)
                    {
                        continue;
                    }

                    $current_count = $this->countCreditedRows(
                        $achievement->trooper_id,
                        $achievement->organization_id,
                        $all_orgs,
                    );

                    if ($current_count < $threshold)
                    {
                        $achievement->forceDelete();
                        $counts['achievements_removed']++;
                    }
                }
            });
    }

    /** @param  Collection<int, Organization>  $all_orgs */
    private function countCreditedRows(int $trooper_id, int $root_org_id, Collection $all_orgs): int
    {
        return EventTrooper::query()
            ->where(EventTrooper::TROOPER_ID, $trooper_id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
            ->whereJsonLength(EventTrooper::COSTUME_ORGANIZATION_IDS, '>', 0)
            ->get([EventTrooper::COSTUME_ORGANIZATION_IDS])
            ->filter(function (EventTrooper $event_trooper) use ($root_org_id, $all_orgs)
            {
                foreach ($event_trooper->costume_organization_ids as $org_id)
                {
                    $node_path = $all_orgs->get($org_id)?->node_path;

                    if ($node_path !== null && Organization::rootIdFromPath($node_path) === $root_org_id)
                    {
                        return true;
                    }
                }

                return false;
            })
            ->count();
    }

    /** @param  array<string, int>  $counts */
    private function printSummary(array $counts): void
    {
        $this->command?->info('Fix409 complete:');
        $this->command?->info("  Credit rows scanned:              {$counts['credit_rows_scanned']}");
        $this->command?->info("  Premature credits removed:        {$counts['premature_credit_removed']}");
        $this->command?->info("  Corrected (partial credit kept):  {$counts['corrected_partial_credit']}");
        $this->command?->info("  Recovered (live eligibility):      {$counts['recovered_live']}");
        $this->command?->info("  Recovered (legacy fallback):       {$counts['recovered_legacy']}");
        $this->command?->info("  Skipped (no eligible org):         {$counts['skipped_no_eligible_org']}");
        $this->command?->info("  Achievement rows removed:          {$counts['achievements_removed']}");
        $this->command?->newLine();
        $this->command?->info('  Run `php artisan tracker:calculate-trooper-achievements` next so any');
        $this->command?->info('  legitimately-earned club milestones are created fresh.');
    }

    /** @param  array<int, array<string, mixed>>  $outstanding_rows */
    private function emailOutstandingCredit(MagicBus $bus, array $outstanding_rows): void
    {
        if (empty($outstanding_rows))
        {
            return;
        }

        $admins = $bus->send(new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR));

        foreach ($admins as $admin)
        {
            Mail::to($admin->email)->queue(new Fix409OutstandingCredit($admin, $outstanding_rows));
        }
    }
}
