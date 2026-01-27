<?php

namespace App\Models;

use App\Enums\AchievementType;
use App\Models\Base\TrooperAchievement as BaseTrooperAchievement;
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

    public const DISPLAY_ORDER = [
        AchievementType::TROOPED_ALL_SQUADS->value => 1,
        AchievementType::TROOPER_EVENTS->value => 2,
        AchievementType::FIRST_TROOP->value => 3,

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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts()
    {
        return array_merge($this->casts, [
            self::TYPE => AchievementType::class,
        ]);
    }
    public function getDisplayOrderAttribute(): int
    {
        return self::DISPLAY_ORDER[$this->achievement] ?? PHP_INT_MAX;
    }

    public function getIconAttribute(): string
    {
        return match ($this->type)
        {
            AchievementType::TROOPED_ALL_SQUADS => 'fa-network-wired',
            AchievementType::TROOPER_EVENTS => 'fa-user-plus',
            AchievementType::FIRST_TROOP => 'fa-flag-checkered',

            AchievementType::TROOPED_10 => 'fa-shield-halved',
            AchievementType::TROOPED_25 => 'fa-user-shield',
            AchievementType::TROOPED_50 => 'fa-medal',
            AchievementType::TROOPED_75 => 'fa-star-half-stroke',
            AchievementType::TROOPED_100 => 'fa-star',
            AchievementType::TROOPED_150 => 'fa-trophy',
            AchievementType::TROOPED_200 => 'fa-helmet-safety',
            AchievementType::TROOPED_250 => 'fa-award',
            AchievementType::TROOPED_300 => 'fa-certificate',
            AchievementType::TROOPED_400 => 'fa-crown',
            AchievementType::TROOPED_500 => 'fa-gem',
            AchievementType::TROOPED_501 => 'fa-brands fa-empire',

            default => 'fa-circle-question',
        };
    }

    public function getTitleAttribute(): string
    {
        return match ($this->type)
        {
            AchievementType::TROOPED_ALL_SQUADS => 'All Squads - Sector Sweep',
            AchievementType::TROOPER_EVENTS => 'Initiated - Trooper Status Achieved',
            AchievementType::FIRST_TROOP => '1 Troop - Mission Initiated',

            AchievementType::TROOPED_10 => '10 Troops - Outer Rim',
            AchievementType::TROOPED_25 => '25 Troops - Garrison Guard',
            AchievementType::TROOPED_50 => '50 Troops - Service Medal',
            AchievementType::TROOPED_75 => '75 Troops - Rising Star',
            AchievementType::TROOPED_100 => '100 Troops - Centurion Crest',
            AchievementType::TROOPED_150 => '150 Troops - Campaign Captain',
            AchievementType::TROOPED_200 => '200 Troops - Elite Status',
            AchievementType::TROOPED_250 => '250 Troops - Command Honor',
            AchievementType::TROOPED_300 => '300 Troops - Doctrine Seal',
            AchievementType::TROOPED_400 => '400 Troops - Core Crown',
            AchievementType::TROOPED_500 => '500 Troops - Kyber Gem',
            AchievementType::TROOPED_501 => '501 Troops - Vader’s Fist',

            default => 'Unknown Achievement',
        };
    }

}
