<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\AchievementType;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\OauthProvider;
use App\Jobs\SendTrooperMilestoneNotificationsJob;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\OauthLogin;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use App\Models\TrooperDonation;
use App\Services\Forums\XenforoService;
use App\Support\XenforoUpgradeHelper;
use Illuminate\Support\Collection;

/**
 * Handler for recalculating a trooper's rank based on their event attendance.
 *
 * Updates the trooper's rank achievement by counting the number of events attended
 * and assigning ranks accordingly.
 *
 * @implements CommandHandlerInterface<RecalculateTrooperRankCommand>
 */
class RecalculateTrooperRankCommandHandler implements CommandHandlerInterface
{
    private int $rank = 1;

    /**
     * @var array{total:int,global:int,club:int,by_type:array<string,int>}
     */
    private array $created_milestones = [
        'total' => 0,
        'global' => 0,
        'club' => 0,
        'by_type' => [],
    ];

    /**
     * Execute the command to recalculate a trooper's rank.
     *
     * @param  RecalculateTrooperRankCommand  $message  The command with trooper rank recalculation data
     */
    public function __invoke(object $message): mixed
    {
        $this->rank = 1;
        $this->created_milestones = [
            'total' => 0,
            'global' => 0,
            'club' => 0,
            'by_type' => [],
        ];

        $with_count = [
            'event_troopers as event_count' => function ($q) {
                $q->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED);
            },
        ];

        $q = Trooper::query()
            ->select([Trooper::ID])
            ->withCount($with_count)
            ->orderByDesc('event_count');

        if ($message->trooper_id !== null)
        {
            $q->where(Trooper::ID, $message->trooper_id);
        }

        // One API call for the entire run — all users' upgrade records.
        $xenforo_upgrades = $this->prefetchXenforoUpgrades();

        $q->chunk(200, function ($troopers) use ($message, $xenforo_upgrades) {
            //  only process rank if we're processing all troopers, otherwise
            //  the rank won't be accurate since we're not reordering all the
            //  troopers by attendance
            $process_rank = $message->trooper_id === null;

            $this->processChunk(
                troopers: $troopers,
                process_rank: $process_rank,
                xenforo_upgrades: $xenforo_upgrades,
                send_milestone_notifications: $message->send_milestone_notifications,
            );
        });

        return [
            'created_milestones' => $this->created_milestones,
        ];
    }

    /**
     * Process a chunk of troopers to calculate and update their achievements.
     *
     * For each trooper, calculates metrics (hours, funds) and updates:
     * - Rank achievement based on attendance ordering
     * - Shift count achievement
     * - Volunteer hours achievement
     * - Direct and indirect funds achievements
     * - Donation metrics and milestone achievements
     * - Troop threshold milestone achievements
     *
     * @param  Collection  $troopers  Chunk of troopers to process
     */
    private function processChunk(
        Collection $troopers,
        bool $process_rank,
        array $xenforo_upgrades,
        bool $send_milestone_notifications,
    ): void {
        // One query per chunk to map trooper_id → xenforo_user_id.
        $trooper_ids = $troopers->pluck('id')->all();

        $xenforo_id_map = OauthLogin::where(OauthLogin::PROVIDER, OauthProvider::XENFORO)
            ->whereIn(OauthLogin::TROOPER_ID, $trooper_ids)
            ->pluck(OauthLogin::PROVIDER_ID, OauthLogin::TROOPER_ID)
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($troopers as $trooper)
        {
            $metrics = $this->computeMetrics($trooper);

            if ($process_rank)
            {
                //  if we're only processing a single trooper, we don't want to update their rank
                //  since it won't be accurate (we're not reordering all the troopers by attendance)
                $this->updateAchievement($trooper, AchievementType::TROOPER_RANK, $this->rank);
            }
            $this->updateAchievement($trooper, AchievementType::TROOPER_SHIFTS, $trooper->event_count);
            $this->updateAchievement($trooper, AchievementType::VOLUNTEER_HOURS, $metrics['total_hours']);
            $this->updateAchievement($trooper, AchievementType::DIRECT_FUNDS, $metrics['total_direct']);
            $this->updateAchievement($trooper, AchievementType::INDIRECT_FUNDS, $metrics['total_indirect']);

            $xenforo_user_id = $xenforo_id_map[$trooper->id] ?? null;
            $donation_metrics = $this->computeDonationMetrics($trooper, $xenforo_user_id, $xenforo_upgrades);
            $this->updateAchievement($trooper, AchievementType::DONATION_MONTHS, $donation_metrics['donation_months']);
            $this->updateAchievement($trooper, AchievementType::TOTAL_DONATED, $donation_metrics['total_donated']);
            $this->storeDonationMilestoneAchievements($trooper, $donation_metrics, $send_milestone_notifications);

            $this->storeTroopThresholdAchievements($trooper, $trooper->event_count, $send_milestone_notifications);
            $this->storeClubTroopThresholdAchievements($trooper, $send_milestone_notifications);

            $this->rank++;
        }
    }

    /**
     * Update or create a trooper's achievement with the given value.
     *
     * Uses updateOrCreate to either update an existing achievement record
     * or create a new one if it doesn't exist. Sets the achievement_date timestamp
     * to the current time.
     *
     * @param  Trooper  $trooper  The trooper whose achievement to update
     * @param  AchievementType  $type  The type of achievement to update
     * @param  mixed  $value  The achievement value (rank number, shift count, hours, funds, etc.)
     */
    private function updateAchievement(Trooper $trooper, AchievementType $type, mixed $value): void
    {
        $where = [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::ORGANIZATION_ID => null,
            TrooperAchievement::TYPE => $type,
        ];

        $set = [
            TrooperAchievement::VALUE => $value,
            TrooperAchievement::ACHIEVEMENT_DATE => now(),
        ];

        TrooperAchievement::updateOrCreate($where, $set);
    }

    /**
     * Compute volunteer metrics for a trooper from their attended events.
     *
     * Aggregates data from all closed events the trooper has attended:
     * - Total direct charity funds raised
     * - Total indirect charity funds raised
     * - Total volunteer hours (shift duration + event charity hours)
     *
     * Only counts events with ATTENDED status and CLOSED event status.
     *
     * @param  Trooper  $trooper  The trooper to compute metrics for
     * @return array{total_direct: int|float, total_indirect: int|float, total_hours: int|float} Computed metrics
     */
    private function computeMetrics(Trooper $trooper): array
    {
        $event_troopers = $trooper->event_troopers()
            ->with('event_shift')
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
            ->whereHas('event_shift.event', fn ($q) => $q->where(Event::STATUS, EventStatus::CLOSED))
            ->get();

        $total_direct = 0;
        $total_indirect = 0;
        $total_hours = 0;

        foreach ($event_troopers as $et)
        {
            $shift = $et->event_shift;

            $total_direct += $shift->charity_direct_funds;
            $total_indirect += $shift->charity_indirect_funds;
            $total_hours += $shift->effective_charity_hours;
        }

        return [
            'total_direct' => $total_direct,
            'total_indirect' => $total_indirect,
            'total_hours' => $total_hours,
        ];
    }

    /**
     * Fetch all upgrade records from XenForo once and return a lookup keyed
     * by xenforo user_id: ['active' => [...], 'expired' => [...]].
     *
     * Embeds the resolved per-period cost_amount into each record so callers
     * do not need to re-derive it. Returns an empty array when XenForo is
     * unavailable or the API call fails.
     *
     * @return array<int,array{active:array<mixed>,expired:array<mixed>}>
     */
    private function prefetchXenforoUpgrades(): array
    {
        $stats = app(XenforoService::class)->get_upgrade_stats();

        if ($stats === null)
        {
            return [];
        }

        // Build cost_amount lookup keyed by user_upgrade_id.
        $cost_map = [];

        foreach ($stats['userUpgrades'] ?? [] as $upgrade)
        {
            if (is_array($upgrade) && isset($upgrade['user_upgrade_id']))
            {
                $cost_map[(int) $upgrade['user_upgrade_id']] = (float) ($upgrade['cost_amount'] ?? 0);
            }
        }

        $lookup = [];

        foreach ($stats['userUpgradeActive'] ?? [] as $row)
        {
            if (is_array($row) && isset($row['user_id']))
            {
                $row['cost_amount'] = XenforoUpgradeHelper::resolveRecordCost($row, $cost_map);
                $lookup[(int) $row['user_id']]['active'][] = $row;
            }
        }

        foreach ($stats['userUpgradeExpired'] ?? [] as $row)
        {
            if (is_array($row) && isset($row['user_id']))
            {
                $row['cost_amount'] = XenforoUpgradeHelper::resolveRecordCost($row, $cost_map);
                $lookup[(int) $row['user_id']]['expired'][] = $row;
            }
        }

        return $lookup;
    }

    /**
     * Compute donation metrics for a trooper.
     *
     * Donation months use XenForo upgrade history when available — this handles
     * both monthly (one record ≈ one month) and annual subscriptions correctly.
     * Falls back to distinct calendar months from local donation records when
     * the trooper has no XenForo link or XenForo data is unavailable.
     *
     * Total donated combines XenForo subscription costs (months × per-period price)
     * with local TrooperDonation records so neither source is excluded.
     *
     * @param  array<int,array{active:array<mixed>,expired:array<mixed>}>  $xenforo_upgrades
     * @return array{donation_months: int, total_donated: float}
     */
    private function computeDonationMetrics(Trooper $trooper, ?int $xenforo_user_id, array $xenforo_upgrades): array
    {
        $donations = $this->loadLocalDonations($trooper);
        $local_total = (float) $donations->sum(fn ($d) => (float) $d->amount);
        $local_keys = $this->buildLocalMonthKeys($donations);

        if ($xenforo_user_id !== null && isset($xenforo_upgrades[$xenforo_user_id]))
        {
            $active = $xenforo_upgrades[$xenforo_user_id]['active'] ?? [];
            $expired = $xenforo_upgrades[$xenforo_user_id]['expired'] ?? [];

            $merged_months = count(array_merge($local_keys, XenforoUpgradeHelper::monthKeysFromUpgrades($active, $expired)));
            $xenforo_total = $this->computeTotalFromUpgrades($active, $expired);

            return [
                'donation_months' => $merged_months,
                'total_donated' => $xenforo_total + $local_total,
            ];
        }

        return [
            'donation_months' => count($local_keys),
            'total_donated' => $local_total,
        ];
    }

    /**
     * Load a trooper's non-deleted local donation records.
     *
     * @return Collection<int, TrooperDonation>
     */
    private function loadLocalDonations(Trooper $trooper): Collection
    {
        return TrooperDonation::where(TrooperDonation::TROOPER_ID, $trooper->id)
            ->get([TrooperDonation::AMOUNT, TrooperDonation::CREATED_AT]);
    }

    /**
     * Build a distinct Y-m => true map from local donation records.
     *
     * @param  Collection<int, TrooperDonation>  $donations
     * @return array<string, true>
     */
    private function buildLocalMonthKeys(Collection $donations): array
    {
        $month_keys = [];

        foreach ($donations as $donation)
        {
            if ($donation->created_at !== null)
            {
                $month_keys[$donation->created_at->format('Y-m')] = true;
            }
        }

        return $month_keys;
    }

    /**
     * Sum the total amount donated across all upgrade records.
     *
     * cost_amount is the per-period price — multiply by months covered per record.
     *
     * @param  array<mixed>  $active
     * @param  array<mixed>  $expired
     */
    private function computeTotalFromUpgrades(array $active, array $expired): float
    {
        $total = 0.0;

        foreach (array_merge($active, $expired) as $row)
        {
            $cost = (float) ($row['cost_amount'] ?? 0);
            $start = (int) ($row['start_date'] ?? 0);
            $end = (int) ($row['end_date'] ?? 0);

            if ($start <= 0 || $cost <= 0.0)
            {
                continue;
            }

            $total += XenforoUpgradeHelper::countMonthsForRecord($start, $end) * $cost;
        }

        return $total;
    }

    /**
     * Store milestone achievements for donation length and cumulative amount.
     *
     * @param  Trooper  $trooper  The trooper to check milestones for
     * @param  array{donation_months: int, total_donated: float}  $metrics
     */
    private function storeDonationMilestoneAchievements(
        Trooper $trooper,
        array $metrics,
        bool $send_milestone_notifications,
    ): void {
        $month_thresholds = [
            AchievementType::SUPPORTER_12_MONTHS->value => 12,
            AchievementType::SUPPORTER_24_MONTHS->value => 24,
            AchievementType::SUPPORTER_36_MONTHS->value => 36,
            AchievementType::SUPPORTER_60_MONTHS->value => 60,
        ];

        foreach ($month_thresholds as $type_value => $threshold)
        {
            if ($metrics['donation_months'] < $threshold)
            {
                continue;
            }

            $type = AchievementType::from($type_value);

            $this->storeMilestoneAchievement($trooper, $type, send_milestone_notifications: $send_milestone_notifications);
        }

        $amount_thresholds = [
            AchievementType::DONATED_100->value => 100,
            AchievementType::DONATED_250->value => 250,
            AchievementType::DONATED_500->value => 500,
            AchievementType::DONATED_1000->value => 1000,
        ];

        foreach ($amount_thresholds as $type_value => $threshold)
        {
            if ($metrics['total_donated'] < $threshold)
            {
                continue;
            }

            $type = AchievementType::from($type_value);

            $this->storeMilestoneAchievement($trooper, $type, send_milestone_notifications: $send_milestone_notifications);
        }
    }

    /**
     * Store milestone achievements for troop count thresholds.
     *
     * Checks each milestone achievement type (FIRST_TROOP, TROOPED_10, etc.)
     * and creates achievement records for any milestones the trooper has reached
     * based on their event count. Only creates new records; existing achievements
     * are preserved.
     *
     * Milestone thresholds: 1, 10, 25, 50, 75, 100, 150, 200, 250, 300, 400, 500, 501
     *
     * @param  Trooper  $trooper  The trooper to check milestones for
     * @param  int  $event_count  The trooper's total attended event count
     */
    private function storeTroopThresholdAchievements(
        Trooper $trooper,
        int $event_count,
        bool $send_milestone_notifications,
    ): void {
        foreach ($this->troopThresholds() as $achievement_value => $threshold)
        {
            $achievement = AchievementType::from($achievement_value);

            if ($event_count < $threshold)
            {
                //  hasn't reached this threshold yet
                continue;
            }

            $this->storeMilestoneAchievement($trooper, $achievement, send_milestone_notifications: $send_milestone_notifications);
        }
    }

    /**
     * Store top-level club troop-count milestone achievements.
     */
    private function storeClubTroopThresholdAchievements(Trooper $trooper, bool $send_milestone_notifications): void
    {
        foreach ($this->computeClubTroopCounts($trooper) as $organization_id => $event_count)
        {
            foreach ($this->troopThresholds() as $achievement_value => $threshold)
            {
                if ($event_count < $threshold)
                {
                    continue;
                }

                $this->storeMilestoneAchievement(
                    trooper: $trooper,
                    type: AchievementType::from($achievement_value),
                    organization_id: (int) $organization_id,
                    send_milestone_notifications: $send_milestone_notifications,
                );
            }
        }
    }

    /**
     * Count attended shifts credited to each top-level club.
     *
     * @return array<int, int>
     */
    private function computeClubTroopCounts(Trooper $trooper): array
    {
        $event_troopers = EventTrooper::where(EventTrooper::TROOPER_ID, $trooper->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
            ->get([
                EventTrooper::ID,
                EventTrooper::ORGANIZATION_ID,
                EventTrooper::COSTUME_ORGANIZATION_IDS,
            ]);

        $candidate_org_ids = $event_troopers
            ->flatMap(function (EventTrooper $event_trooper): array {
                $costume_org_ids = $event_trooper->costume_organization_ids ?? [];

                return !empty($costume_org_ids)
                    ? $costume_org_ids
                    : array_filter([$event_trooper->organization_id]);
            })
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($candidate_org_ids->isEmpty())
        {
            return [];
        }

        $node_paths = Organization::whereIn(Organization::ID, $candidate_org_ids->all())
            ->pluck(Organization::NODE_PATH, Organization::ID);

        $counts = [];

        foreach ($event_troopers as $event_trooper)
        {
            $costume_org_ids = $event_trooper->costume_organization_ids ?? [];
            $credited_org_ids = !empty($costume_org_ids)
                ? $costume_org_ids
                : array_filter([$event_trooper->organization_id]);

            $club_ids = collect($credited_org_ids)
                ->map(fn ($id) => $node_paths[(int) $id] ?? null)
                ->filter()
                ->map(fn (string $node_path): int => (int) explode(Organization::NODE_PATH_SEP, $node_path)[0])
                ->unique();

            foreach ($club_ids as $club_id)
            {
                $counts[$club_id] = ($counts[$club_id] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * @return array<string, int>
     */
    private function troopThresholds(): array
    {
        return [
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
    }

    private function storeMilestoneAchievement(
        Trooper $trooper,
        AchievementType $type,
        ?int $organization_id = null,
        bool $send_milestone_notifications = true,
    ): void {
        $achievement = TrooperAchievement::query()
            ->where(TrooperAchievement::TROOPER_ID, $trooper->id)
            ->where(TrooperAchievement::ORGANIZATION_ID, $organization_id)
            ->where(TrooperAchievement::TYPE, $type)
            ->first();

        if ($achievement == null)
        {
            $achievement = new TrooperAchievement;
            $achievement->{TrooperAchievement::TROOPER_ID} = $trooper->id;
            $achievement->{TrooperAchievement::ORGANIZATION_ID} = $organization_id;
            $achievement->{TrooperAchievement::TYPE} = $type;
            $achievement->{TrooperAchievement::VALUE} = true;
            $achievement->{TrooperAchievement::ACHIEVEMENT_DATE} = now();
            $achievement->save();

            $this->recordCreatedMilestone($achievement);
        }

        if ($achievement->notification_sent_at !== null)
        {
            return;
        }

        if (!$send_milestone_notifications)
        {
            $achievement->notification_sent_at = now();
            $achievement->save();

            return;
        }

        if ($send_milestone_notifications)
        {
            $achievement->setRelation('trooper', $trooper);

            dispatch(new SendTrooperMilestoneNotificationsJob($achievement));
        }
    }

    private function recordCreatedMilestone(TrooperAchievement $achievement): void
    {
        $type = $achievement->type->value;

        $this->created_milestones['total']++;
        $this->created_milestones[$achievement->organization_id === null ? 'global' : 'club']++;
        $this->created_milestones['by_type'][$type] = ($this->created_milestones['by_type'][$type] ?? 0) + 1;
    }
}
