<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Merge;

use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Hyperdrive\Message;

/**
 * Merges trooper achievements from a source trooper into a target trooper.
 * This command ensures that all achievements of the source trooper
 * are transferred to the target trooper, maintaining data integrity and consistency.
 *
 * @method static void call(Trooper $target_trooper, Trooper $source_trooper)
 */
final class MergeTrooperAchievements extends Message
{
    use DateConcerns;

    public function __construct(
        private readonly Trooper $target_trooper,
        private readonly Trooper $source_trooper,
    ) {
    }

    public function handle(): void
    {
        $source_achievements = TrooperAchievement::query()
            ->withTrashed()
            ->where(TrooperAchievement::TROOPER_ID, $this->source_trooper->id)
            ->orderBy(TrooperAchievement::ID)
            ->get();

        foreach ($source_achievements as $source_achievement)
        {
            $target_achievement = $this->getTargetAchievement($source_achievement);

            if ($target_achievement === null)
            {
                $source_achievement->trooper_id = $this->target_trooper->id;
                $source_achievement->save();

                continue;
            }

            $this->mergeAchievements($target_achievement, $source_achievement);
        }
    }

    private function getTargetAchievement(TrooperAchievement $source_achievement, ): ?TrooperAchievement
    {
        return TrooperAchievement::query()
            ->withTrashed()
            ->where(TrooperAchievement::TROOPER_ID, $this->target_trooper->id)
            ->where(TrooperAchievement::TYPE, $source_achievement->type)
            ->where(
                TrooperAchievement::ORGANIZATION_COALESCE_ID,
                $source_achievement->organization_coalesce_id,
            )
            ->first();
    }

    private function mergeAchievements(TrooperAchievement $target_achievement, TrooperAchievement $source_achievement, ): void
    {
        if ($target_achievement->trashed() && !$source_achievement->trashed())
        {
            $target_achievement->restore();
        }

        $source_achievement->forceDelete();

        $target_achievement->value = $source_achievement->value ?? $target_achievement->value;
        $target_achievement->achievement_date = $this->earliestDateTime(
            $target_achievement->achievement_date,
            $source_achievement->achievement_date,
        );
        $target_achievement->notification_sent_at = $this->latestDateTime(
            $target_achievement->notification_sent_at,
            $source_achievement->notification_sent_at,
        );
        $target_achievement->save();
    }
}
