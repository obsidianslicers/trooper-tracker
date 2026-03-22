<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\AchievementType;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
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

        $q->chunk(200, function ($troopers) use ($message) {
            //  only process rank if we're processing all troopers, otherwise
            //  the rank won't be accurate since we're not reordering all the
            //  troopers by attendance
            $process_rank = $message->trooper_id === null;

            $this->processChunk($troopers, $process_rank);
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
    private function processChunk($troopers, bool $process_rank): void
    {
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
