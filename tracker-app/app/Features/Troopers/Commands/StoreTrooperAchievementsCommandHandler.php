<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
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
        $trooper_events = $message->trooper_stats;

        for ($i = 0, $len = $trooper_events->count(); $i < $len; $i++)
        {
            $trooper_event = $trooper_events[$i];
            $count = $trooper_event->event_count;
            $hours = $trooper_event->total_hours;
            $direct_funds = $trooper_event->total_direct;
            $indirect_funds = $trooper_event->total_indirect;

            $where = [TrooperAchievement::TROOPER_ID => $trooper_events[$i]->trooper_id];

            $values = [
                TrooperAchievement::TROOPER_RANK => ($i + 1),
                TrooperAchievement::TROOPER_EVENTS => $count,
                TrooperAchievement::FIRST_TROOP_COMPLETED => $count >= 1,
                TrooperAchievement::TROOPED_10 => $count >= 10,
                TrooperAchievement::TROOPED_25 => $count >= 25,
                TrooperAchievement::TROOPED_50 => $count >= 50,
                TrooperAchievement::TROOPED_75 => $count >= 75,
                TrooperAchievement::TROOPED_100 => $count >= 100,
                TrooperAchievement::TROOPED_150 => $count >= 150,
                TrooperAchievement::TROOPED_200 => $count >= 200,
                TrooperAchievement::TROOPED_250 => $count >= 250,
                TrooperAchievement::TROOPED_300 => $count >= 300,
                TrooperAchievement::TROOPED_400 => $count >= 400,
                TrooperAchievement::TROOPED_500 => $count >= 500,
                TrooperAchievement::TROOPED_501 => $count >= 501,
                TrooperAchievement::VOLUNTEER_HOURS => $hours,
                TrooperAchievement::DIRECT_FUNDS => $direct_funds,
                TrooperAchievement::INDIRECT_FUNDS => $indirect_funds,
            ];

            TrooperAchievement::updateOrCreate($where, $values);
        }

        return null;
    }
}
