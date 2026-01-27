<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\AchievementType;
use App\Models\TrooperAchievement;

/**
 * Handler for storing or updating trooper achievements.
 *
 * Iterates through trooper event statistics, determines which achievement
 * thresholds have been met, and updates the trooper_achievements table.
 * Assigns trooper ranks based on total event count.
 *
 * @implements CommandHandlerInterface<StoreTrooperAchievementsCommand>
 */
readonly class StoreTrooperAchievementsCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to store trooper achievements.
     *
     * Process:
     * 1. Iterate through trooper event statistics (ordered by count desc)
     * 2. Calculate which achievement thresholds are met for each trooper
     * 3. Update or create trooper_achievement record with:
     *    - Trooper rank (based on position in ordered list)
     *    - Achievement flags (1 troop, 10 troops, 25 troops, etc.)
     *    - Volunteer hours total
     *    - Direct and indirect funds raised totals
     *
     * @param StoreTrooperAchievementsCommand $message The command with trooper event stats
     * @return null
     */
    public function __invoke(object $message): mixed
    {
        $troopers = $message->trooper_stats;

        foreach ($troopers as $index => $trooper)
        {
            foreach (AchievementType::cases() as $achievement)
            {
                $value = match ($achievement)
                {
                    AchievementType::TROOPER_RANK => $index + 1,
                    AchievementType::TROOPER_EVENTS => $trooper->event_count,
                    AchievementType::VOLUNTEER_HOURS => $trooper->total_hours,
                    AchievementType::DIRECT_FUNDS => $trooper->total_direct,
                    AchievementType::INDIRECT_FUNDS => $trooper->total_indirect,

                    AchievementType::TROOPED_ALL_SQUADS => $trooper->trooped_all_squads ?? false,
                    AchievementType::FIRST_TROOP => $trooper->event_count >= 1,

                    AchievementType::TROOPED_10 => $trooper->event_count >= 10,
                    AchievementType::TROOPED_25 => $trooper->event_count >= 25,
                    AchievementType::TROOPED_50 => $trooper->event_count >= 50,
                    AchievementType::TROOPED_75 => $trooper->event_count >= 75,
                    AchievementType::TROOPED_100 => $trooper->event_count >= 100,
                    AchievementType::TROOPED_150 => $trooper->event_count >= 150,
                    AchievementType::TROOPED_200 => $trooper->event_count >= 200,
                    AchievementType::TROOPED_250 => $trooper->event_count >= 250,
                    AchievementType::TROOPED_300 => $trooper->event_count >= 300,
                    AchievementType::TROOPED_400 => $trooper->event_count >= 400,
                    AchievementType::TROOPED_500 => $trooper->event_count >= 500,
                    AchievementType::TROOPED_501 => $trooper->event_count >= 501,
                };

                // Skip false boolean achievements
                if ($value === false)
                {
                    continue;
                }

                $where = [
                    TrooperAchievement::TROOPER_ID => $trooper->id,
                    TrooperAchievement::TYPE => $achievement->value,
                ];

                $set = [
                    TrooperAchievement::VALUE => is_bool($value) ? null : $value,
                    TrooperAchievement::EARNED_ON => now(),
                ];

                TrooperAchievement::firstOrCreate($where, $set);
            }
        }

        return null;
    }

}
