<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison;

use App\Enums\AchievementType;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;

class TrooperAchievementSeeder extends Seeder
{
    private int $rank = 1;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $date_ranges = $this->getDateRanges('2000-01-01');

        foreach ($date_ranges as $date_range)
        {
            [$start_date, $end_date] = $date_range;

            $this->rank = 1; // reset rank for each date range

            $this->command->info("   Processing achievements for date range: {$start_date} to {$end_date}");

            $start_date = Carbon::parse($start_date);
            $end_date = Carbon::parse($end_date);

            $this->runFor($start_date, $end_date);
        }
    }

    public function runFor(Carbon $start_date, Carbon $end_date): mixed
    {
        $with_count = [
            'event_troopers as event_count' => function ($q) use ($end_date)
            {
                $q->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
                    ->whereHas('event_shift.event', function ($q) use ($end_date)
                    {
                        $q->where(Event::STATUS, EventStatus::CLOSED)
                            ->where(Event::EVENT_END, '<=', $end_date);
                    })
                    ->whereHas('event_shift', function ($q) use ($end_date)
                    {
                        $q->where(EventShift::STATUS, EventStatus::CLOSED)
                            ->where(EventShift::SHIFT_STARTS_AT, '<=', $end_date);
                    });
            },
        ];

        $q = Trooper::query()
            ->select([Trooper::ID])
            ->withCount($with_count)
            ->orderByDesc('event_count');

        $q->chunk(300, function ($troopers) use ($start_date, $end_date)
        {
            //  only process rank if we're processing all troopers, otherwise
            //  the rank won't be accurate since we're not reordering all the
            //  troopers by attendance
            $process_rank = true;

            $this->processChunk($troopers, $process_rank, $start_date, $end_date);
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
     * @param  \Illuminate\Support\Collection  $troopers  Chunk of troopers to process
     */
    private function processChunk($troopers, bool $process_rank, Carbon $start_date, Carbon $end_date): void
    {
        foreach ($troopers as $trooper)
        {
            $metrics = $this->computeMetrics($trooper, $start_date, $end_date);

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

            $this->storeTroopThresholdAchievements($trooper, $trooper->event_count, $end_date);

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
    private function computeMetrics(Trooper $trooper, Carbon $start_date, Carbon $end_date): array
    {
        $event_troopers = $trooper->event_troopers()
            ->with('event_shift.event')
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
            ->whereHas('event_shift.event', fn($q) => $q->where(Event::STATUS, EventStatus::CLOSED))
            ->whereHas('event_shift', function ($q) use ($end_date)
            {
                $q->where(EventShift::STATUS, EventStatus::CLOSED)
                    ->where(EventShift::SHIFT_STARTS_AT, '<=', $end_date);
            })
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
    private function storeTroopThresholdAchievements(Trooper $trooper, int $event_count, Carbon $end_date): void
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
                TrooperAchievement::ACHIEVEMENT_DATE => $end_date,
            ];

            TrooperAchievement::firstOrCreate($where, $set);
        }
    }


    private function getDateRanges(string $start_date_string): array
    {
        $start = Carbon::parse($start_date_string)->startOfDay();
        $today = Carbon::today();
        $start_of_current_year = $today->copy()->startOfYear();
        $ranges = [];

        // Phase 1: Five-year chunks up through 2020.
        $end_of_2020 = Carbon::create(2020, 12, 31)->endOfDay()->min($today);

        if ($start->lte($end_of_2020))
        {
            $chunk_cursor = $start->copy()->startOfYear();

            while ($chunk_cursor->lte($end_of_2020))
            {
                $chunk_start = $chunk_cursor->copy()->startOfYear()->max($start);
                $chunk_end = $chunk_cursor->copy()->addYears(4)->endOfYear()->min($end_of_2020);

                if ($chunk_start->lte($chunk_end))
                {
                    $ranges[] = [$chunk_start->format('Y-m-d'), $chunk_end->format('Y-m-d')];
                }

                $chunk_cursor->addYears(5);
            }
        }

        // Phase 2: Year-by-year from 2021 through 2024.
        $start_of_2021 = Carbon::create(2021, 1, 1)->startOfDay();
        $end_of_2024 = Carbon::create(2024, 12, 31)->endOfDay()->min($today);
        $yearly_phase_start = $start->gt($start_of_2021) ? $start : $start_of_2021;

        if ($yearly_phase_start->lte($end_of_2024))
        {
            $year_cursor = $yearly_phase_start->copy()->startOfYear();

            while ($year_cursor->lte($end_of_2024))
            {
                $year_start = $year_cursor->copy()->startOfYear()->max($yearly_phase_start);
                $year_end = $year_cursor->copy()->endOfYear()->min($end_of_2024);

                if ($year_start->lte($year_end))
                {
                    $ranges[] = [$year_start->format('Y-m-d'), $year_end->format('Y-m-d')];
                }

                $year_cursor->addYear();
            }
        }

        // Phase 3: Quarter-by-quarter for 2025.
        $start_of_2025 = Carbon::create(2025, 1, 1)->startOfDay();
        $end_of_2025 = Carbon::create(2025, 12, 31)->endOfDay()->min($today);
        $quarterly_phase_start = $start->gt($start_of_2025) ? $start : $start_of_2025;

        if ($quarterly_phase_start->lte($end_of_2025))
        {
            $quarter_cursor = $quarterly_phase_start->copy()->startOfQuarter();

            while ($quarter_cursor->lte($end_of_2025))
            {
                $quarter_start = $quarter_cursor->copy()->startOfQuarter()->max($quarterly_phase_start);
                $quarter_end = $quarter_cursor->copy()->endOfQuarter()->min($end_of_2025);

                if ($quarter_start->lte($quarter_end))
                {
                    $ranges[] = [$quarter_start->format('Y-m-d'), $quarter_end->format('Y-m-d')];
                }

                $quarter_cursor->addQuarter();
            }
        }

        // Phase 4: Week-by-week for the current year.
        $current_year_phase_start = $start->gt($start_of_current_year)
            ? $start
            : $start_of_current_year;

        if ($current_year_phase_start->lte($today))
        {
            $week_cursor = $current_year_phase_start->copy()->startOfWeek(Carbon::SUNDAY);

            while ($week_cursor->lte($today))
            {
                $week_start = $week_cursor->copy()->startOfWeek(Carbon::SUNDAY)
                    ->max($current_year_phase_start);
                $week_end = $week_cursor->copy()->endOfWeek(Carbon::SATURDAY)->min($today);

                if ($week_start->lte($week_end))
                {
                    $ranges[] = [$week_start->format('Y-m-d'), $week_end->format('Y-m-d')];
                }

                if ($week_end->equalTo($today))
                {
                    break;
                }

                $week_cursor->addWeek();
            }
        }

        return $ranges;
    }
}