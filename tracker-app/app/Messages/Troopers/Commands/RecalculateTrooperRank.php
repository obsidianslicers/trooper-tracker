<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Contracts\DonationSource;
use App\Enums\AchievementType;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use App\Models\TrooperDonation;
use App\Plugins\PluginDonations;
use App\Support\DonationSummary;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Command to recalculate trooper rank and achievements.
 *
 * Recalculates a trooper's rank based on their event attendance and updates
 * all achievement records including rank, shift count, volunteer hours,
 * charity funds, and milestone achievements.
 *
 * When trooper_id is null, processes all troopers in the system.
 * When trooper_id is provided, processes only that specific trooper.
 * Milestone notifications are enabled by default and can be disabled for
 * large backfills.
 *
 * @method static array call(?int $trooper_id = null, bool $send_milestone_notifications = true)
 */
final class RecalculateTrooperRank extends Message
{
    /**
     * Create a new command instance.
     *
     * @param  int|null  $trooper_id  The ID of the trooper to recalculate rank for, or null to recalculate all troopers
     * @param  bool  $send_milestone_notifications  Whether unsent milestone achievements should notify command staff
     */
    public function __construct(
        private readonly ?int $trooper_id = null,
        private readonly bool $send_milestone_notifications = true,
    ) {}

    /**
     * Execute the command to recalculate a trooper's rank.
     */
    public function handle(): array
    {
        $this->rank = 1;
        $this->created_milestones = [
            'total' => 0,
            'global' => 0,
            'club' => 0,
            'by_type' => [],
        ];

        $with_count = [
            'event_troopers as event_count' => function ($q)
            {
                $q->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED);
            },
        ];

        $q = Trooper::query()
            ->select([Trooper::ID])
            ->withCount($with_count)
            ->orderByDesc('event_count');

        if ($this->trooper_id !== null)
        {
            $q->where(Trooper::ID, $this->trooper_id);
        }

        // Fresh sources per run, so each can fetch its data once for the whole run.
        $donation_sources = app(PluginDonations::class)->sources();

        $q->chunk(200, function ($troopers) use ($donation_sources)
        {
            //  only process rank if we're processing all troopers, otherwise
            //  the rank won't be accurate since we're not reordering all the
            //  troopers by attendance
            $process_rank = $this->trooper_id === null;

            $this->processChunk(
                troopers: $troopers,
                process_rank: $process_rank,
                donation_sources: $donation_sources,
                send_milestone_notifications: $this->send_milestone_notifications,
            );
        });

        return [
            'created_milestones' => $this->created_milestones,
        ];
    }

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
        array $donation_sources,
        bool $send_milestone_notifications,
    ): void {
        $external_donations = $this->externalDonations(
            $donation_sources,
            $troopers->pluck('id')->all(),
        );

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

            $donation_metrics = $this->computeDonationMetrics(
                $trooper,
                $external_donations[$trooper->id] ?? [],
            );
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
     * Each donation source's summaries for a chunk of troopers.
     *
     * @param  array<int, DonationSource>  $sources
     * @param  array<int, int>  $trooper_ids
     * @return array<int, array<int, DonationSummary>> Keyed by trooper ID.
     */
    private function externalDonations(array $sources, array $trooper_ids): array
    {
        $by_trooper = [];

        foreach ($sources as $source)
        {
            foreach ($source->summariesFor($trooper_ids) as $trooper_id => $summary)
            {
                $by_trooper[$trooper_id][] = $summary;
            }
        }

        return $by_trooper;
    }

    /**
     * Compute donation metrics for a trooper.
     *
     * Months are the distinct calendar months with a donation, across local records
     * and every donation source (for example forum subscriptions). The total adds
     * them all, so no source is left out.
     *
     * @param  array<int, DonationSummary>  $external
     * @return array{donation_months: int, total_donated: float}
     */
    private function computeDonationMetrics(Trooper $trooper, array $external): array
    {
        $donations = $this->loadLocalDonations($trooper);
        $month_keys = $this->buildLocalMonthKeys($donations);
        $total = (float) $donations->sum(fn (TrooperDonation $d): float => (float) $d->amount);

        foreach ($external as $summary)
        {
            $month_keys = array_merge($month_keys, $summary->month_keys);
            $total += $summary->total;
        }

        return [
            'donation_months' => count($month_keys),
            'total_donated' => $total,
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
            ->flatMap(function (EventTrooper $event_trooper): array
            {
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

        // Pending milestones are delivered together by the daily roundup command.
    }

    private function recordCreatedMilestone(TrooperAchievement $achievement): void
    {
        $type = $achievement->type->value;

        $this->created_milestones['total']++;
        $this->created_milestones[$achievement->organization_id === null ? 'global' : 'club']++;
        $this->created_milestones['by_type'][$type] = ($this->created_milestones['by_type'][$type] ?? 0) + 1;
    }
}
