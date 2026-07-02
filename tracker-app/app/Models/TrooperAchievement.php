<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AchievementType;
use App\Models\Base\TrooperAchievement as BaseTrooperAchievement;
use App\Models\Casts\AchievementValueCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Represents an achievement or milestone earned by a trooper.
 *
 * Achievements track special accomplishments or milestones that troopers
 * reach, such as event count milestones, special recognitions, or participation
 * in unique activities.
 */
class TrooperAchievement extends BaseTrooperAchievement
{
    use HasFactory;

    /**
     * Display order for achievements.
     *
     * Maps achievement type values to their sort order for UI display.
     * Lower numbers appear first. Unmapped achievements default to PHP_INT_MAX.
     */
    public const DISPLAY_ORDER = [
            //  metrics
        AchievementType::TROOPER_RANK->value => 1,
        AchievementType::TROOPER_SHIFTS->value => 2,
        AchievementType::VOLUNTEER_HOURS->value => 3,
        AchievementType::DIRECT_FUNDS->value => 4,
        AchievementType::INDIRECT_FUNDS->value => 5,
            //  milestones
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

    /**
     * Whether this achievement belongs to a specific top-level club.
     */
    public function isClubScoped(): bool
    {
        return $this->organization_id !== null;
    }

    /**
     * Achievement description with club context when scoped to an organization.
     */
    public function getDisplayDescriptionAttribute(): string
    {
        $description = $this->type->toDescription();

        if ($this->organization_id === null)
        {
            return $description;
        }

        return "{$this->organization?->name}: {$description}";
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts()
    {
        return array_merge($this->casts, [
            self::TYPE => AchievementType::class,
            self::VALUE => AchievementValueCast::class,
        ]);
    }

    /**
     * Get the display order for this achievement.
     *
     * Returns a numeric value used for sorting achievements in the UI.
     * Known achievements use predefined order values, while unknown
     * achievements are sorted to the end (PHP_INT_MAX).
     *
     * @return int Sort order value
     */
    public function getDisplayOrderAttribute(): int
    {
        //  we only have display orders for known achievements
        //  unknown achievements go to the end of the list
        //  and are not part of the achievement enumerable list
        //  but displayed as one-offs
        return self::DISPLAY_ORDER[$this->type->value] ?? PHP_INT_MAX;
    }
}
