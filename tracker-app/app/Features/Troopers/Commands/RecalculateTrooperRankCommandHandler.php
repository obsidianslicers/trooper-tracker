<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\AchievementType;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\OauthProvider;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\OauthLogin;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use App\Models\TrooperDonation;
use Carbon\Carbon;
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
     * Execute the command to recalculate a trooper's rank.
     *
     * @param  RecalculateTrooperRankCommand  $message  The command with trooper rank recalculation data
     * @return null
     */
    public function __invoke(object $message): mixed
    {
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

            $this->processChunk($troopers, $process_rank, $xenforo_upgrades);
        });

        return null;
    }

    /**
     * Process a chunk of troopers to calculate and update their achievements.
     *
     * For each trooper, calculates metrics (hours, funds) and updates:
     * - Rank achievement based on attendance ordering
     * - Shift count achievement
     * - Volunteer hours achievement
     * - Direct and indirect funds achievements
     * - Troop threshold milestone achievements
     *
     * @param  Collection  $troopers  Chunk of troopers to process
     */
    private function processChunk($troopers, bool $process_rank, array $xenforo_upgrades): void
    {
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
            $this->storeDonationMilestoneAchievements($trooper, $donation_metrics);

            $this->storeTroopThresholdAchievements($trooper, $trooper->event_count);

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
            ->with('event_shift.event')
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
            ->whereHas('event_shift.event', fn ($q) => $q->where(Event::STATUS, EventStatus::CLOSED))
            ->get();

        $total_direct = 0;
        $total_indirect = 0;
        $total_hours = 0;

        foreach ($event_troopers as $et)
        {
            $event = $et->event_shift->event;

            $total_direct += $event->charity_direct_funds;
            $total_indirect += $event->charity_indirect_funds;

            $shift = $et->event_shift;
            $total_hours += $shift->shift_starts_at->diffInHours($shift->shift_ends_at) + $event->charity_hours;
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
     * Returns an empty array when XenForo is not configured or the call fails.
     *
     * @return array<int,array{active:array<mixed>,expired:array<mixed>}>
     */
    private function prefetchXenforoUpgrades(): array
    {
        $stats = app(\App\Services\Forums\XenforoService::class)->get_upgrade_stats();

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
                $row['cost_amount'] = $cost_map[(int) ($row['user_upgrade_id'] ?? 0)] ?? 0.0;
                $lookup[(int) $row['user_id']]['active'][] = $row;
            }
        }

        foreach ($stats['userUpgradeExpired'] ?? [] as $row)
        {
            if (is_array($row) && isset($row['user_id']))
            {
                $row['cost_amount'] = $cost_map[(int) ($row['user_upgrade_id'] ?? 0)] ?? 0.0;
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
     * Total donated uses XenForo upgrade subscription costs when available (embedded
     * via cost_amount during prefetch), falling back to local TrooperDonation records.
     *
     * @param  array<int,array{active:array<mixed>,expired:array<mixed>}>  $xenforo_upgrades
     * @return array{donation_months: int, total_donated: float}
     */
    private function computeDonationMetrics(Trooper $trooper, ?int $xenforo_user_id, array $xenforo_upgrades): array
    {
        if ($xenforo_user_id !== null && isset($xenforo_upgrades[$xenforo_user_id]))
        {
            $user_upgrades  = $xenforo_upgrades[$xenforo_user_id];
            $active         = $user_upgrades['active'] ?? [];
            $expired        = $user_upgrades['expired'] ?? [];

            $donation_months = $this->computeMonthsFromUpgrades($active, $expired);

            // cost_amount is the per-period price — multiply by months covered per record.
            $total_donated = $this->computeTotalFromUpgrades($active, $expired);

            return [
                'donation_months' => $donation_months,
                'total_donated'   => $total_donated,
            ];
        }

        // Fallback: no XenForo link — use local donation records.
        $donations = TrooperDonation::where(TrooperDonation::TROOPER_ID, $trooper->id)
            ->whereNull('deleted_at')
            ->get([TrooperDonation::AMOUNT, TrooperDonation::CREATED_AT]);

        $total_donated = (float) $donations->sum(fn ($d) => (float) $d->amount);

        $month_keys = [];

        foreach ($donations as $donation)
        {
            if ($donation->created_at !== null)
            {
                $month_keys[$donation->created_at->format('Y-m')] = true;
            }
        }

        return [
            'donation_months' => count($month_keys),
            'total_donated'   => $total_donated,
        ];
    }

    /**
     * Count distinct calendar months covered by a set of upgrade records.
     *
     * @param  array<mixed>  $active
     * @param  array<mixed>  $expired
     */
    private function computeMonthsFromUpgrades(array $active, array $expired): int
    {
        $months = [];
        $now    = time();

        foreach (array_merge($active, $expired) as $row)
        {
            $start = (int) ($row['start_date'] ?? 0);
            $end   = (int) ($row['end_date'] ?? 0);

            if ($start <= 0)
            {
                continue;
            }

            if ($end === 0 || $end > $now)
            {
                $end = $now;
            }

            $current   = Carbon::createFromTimestamp($start)->startOfMonth();
            $end_month = Carbon::createFromTimestamp($end)->startOfMonth();

            while ($current->lte($end_month))
            {
                $months[$current->format('Y-m')] = true;
                $current->addMonth();
            }
        }

        return count($months);
    }

    /**
     * Sum the total amount donated across all upgrade records.
     *
     * cost_amount is the per-period price, so we multiply by the number of
     * distinct calendar months each record covers.
     *
     * @param  array<mixed>  $active
     * @param  array<mixed>  $expired
     */
    private function computeTotalFromUpgrades(array $active, array $expired): float
    {
        $total = 0.0;
        $now   = time();

        foreach (array_merge($active, $expired) as $row)
        {
            $cost  = (float) ($row['cost_amount'] ?? 0);
            $start = (int) ($row['start_date'] ?? 0);
            $end   = (int) ($row['end_date'] ?? 0);

            if ($start <= 0 || $cost <= 0)
            {
                continue;
            }

            if ($end === 0 || $end > $now)
            {
                $end = $now;
            }

            $current   = Carbon::createFromTimestamp($start)->startOfMonth();
            $end_month = Carbon::createFromTimestamp($end)->startOfMonth();
            $months    = 0;

            while ($current->lte($end_month))
            {
                $months++;
                $current->addMonth();
            }

            $total += $months * $cost;
        }

        return $total;
    }

    /**
     * Store milestone achievements for donation length and cumulative amount.
     *
     * @param  Trooper  $trooper  The trooper to check milestones for
     * @param  array{donation_months: int, total_donated: float}  $metrics
     */
    private function storeDonationMilestoneAchievements(Trooper $trooper, array $metrics): void
    {
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

            TrooperAchievement::firstOrCreate(
                [TrooperAchievement::TROOPER_ID => $trooper->id, TrooperAchievement::TYPE => $type],
                [TrooperAchievement::VALUE => true, TrooperAchievement::ACHIEVEMENT_DATE => now()]
            );
        }

        $amount_thresholds = [
            AchievementType::DONATED_100->value  => 100,
            AchievementType::DONATED_250->value  => 250,
            AchievementType::DONATED_500->value  => 500,
            AchievementType::DONATED_1000->value => 1000,
        ];

        foreach ($amount_thresholds as $type_value => $threshold)
        {
            if ($metrics['total_donated'] < $threshold)
            {
                continue;
            }

            $type = AchievementType::from($type_value);

            TrooperAchievement::firstOrCreate(
                [TrooperAchievement::TROOPER_ID => $trooper->id, TrooperAchievement::TYPE => $type],
                [TrooperAchievement::VALUE => true, TrooperAchievement::ACHIEVEMENT_DATE => now()]
            );
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
    private function storeTroopThresholdAchievements(Trooper $trooper, int $event_count): void
    {
        foreach (AchievementType::cases() as $achievement)
        {
            $threshold = match ($achievement)
            {
                AchievementType::FIRST_TROOP => 1,
                AchievementType::TROOPED_10 => 10,
                AchievementType::TROOPED_25 => 25,
                AchievementType::TROOPED_50 => 50,
                AchievementType::TROOPED_75 => 75,
                AchievementType::TROOPED_100 => 100,
                AchievementType::TROOPED_150 => 150,
                AchievementType::TROOPED_200 => 200,
                AchievementType::TROOPED_250 => 250,
                AchievementType::TROOPED_300 => 300,
                AchievementType::TROOPED_400 => 400,
                AchievementType::TROOPED_500 => 500,
                AchievementType::TROOPED_501 => 501,
                default => null,
            };

            if ($threshold === null || $event_count < $threshold)
            {
                //  not a troop threshold achievement or
                //  hasn't reached this threshold yet
                continue;
            }

            $where = [
                TrooperAchievement::TROOPER_ID => $trooper->id,
                TrooperAchievement::TYPE => $achievement,
            ];

            $set = [
                TrooperAchievement::VALUE => true,
                TrooperAchievement::ACHIEVEMENT_DATE => now(),
            ];

            TrooperAchievement::firstOrCreate($where, $set);
        }
    }
}
