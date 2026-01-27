<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Trooper achievement types and milestones.
 *
 * Defines the various achievement categories tracked in the tt_trooper_achievements table,
 * including event participation milestones, volunteer statistics, and fundraising metrics.
 */
enum AchievementType: string
{
    /**
     * Current rank of the trooper
     */
    case TROOPER_RANK = 'trooper_rank';

    /**
     * Total number of event shifts the trooper has participated in
     */
    case TROOPER_SHIFTS = 'trooper_shifts';

    /**
     * Total volunteer hours contributed
     */
    case VOLUNTEER_HOURS = 'volunteer_hours';

    /**
     * Total direct funds raised for charities
     */
    case DIRECT_FUNDS = 'direct_funds';

    /**
     * Total indirect funds raised for charities
     */
    case INDIRECT_FUNDS = 'indirect_funds';

    /**
     * Achievement for trooping with all squads in the organization
     */
    case TROOPED_ALL_SQUADS = 'trooped_all_squads';

    /**
     * Achievement for completing the first troop
     */
    case FIRST_TROOP = 'first_troop';

    /**
     * Milestone for completing 10 troops
     */
    case TROOPED_10 = 'trooped_10';

    /**
     * Milestone for completing 25 troops
     */
    case TROOPED_25 = 'trooped_25';

    /**
     * Milestone for completing 50 troops
     */
    case TROOPED_50 = 'trooped_50';

    /**
     * Milestone for completing 75 troops
     */
    case TROOPED_75 = 'trooped_75';

    /**
     * Milestone for completing 100 troops
     */
    case TROOPED_100 = 'trooped_100';

    /**
     * Milestone for completing 150 troops
     */
    case TROOPED_150 = 'trooped_150';

    /**
     * Milestone for completing 200 troops
     */
    case TROOPED_200 = 'trooped_200';

    /**
     * Milestone for completing 250 troops
     */
    case TROOPED_250 = 'trooped_250';

    /**
     * Milestone for completing 300 troops
     */
    case TROOPED_300 = 'trooped_300';

    /**
     * Milestone for completing 400 troops
     */
    case TROOPED_400 = 'trooped_400';

    /**
     * Milestone for completing 500 troops
     */
    case TROOPED_500 = 'trooped_500';

    /**
     * Milestone for completing 501 troops (special 501st Legion milestone)
     */
    case TROOPED_501 = 'trooped_501';

    /**
     * Determine the value type for this achievement.
     *
     * Returns the data type used to store the achievement value:
     * - 'number' for metric achievements (counts, hours, funds)
     * - 'bool' for milestone achievements (earned or not earned)
     *
     * This is used by AchievementValueCast to properly cast database values.
     *
     * @return string Either 'number' or 'bool'
     */
    public function valueType(): string
    {
        return match ($this)
        {
            self::TROOPER_RANK,
            self::TROOPER_SHIFTS,
            self::VOLUNTEER_HOURS,
            self::DIRECT_FUNDS,
            self::INDIRECT_FUNDS => 'number',

            default => 'bool',
        };
    }

    /**
     * Get the Font Awesome icon class for this achievement.
     *
     * Returns the appropriate Font Awesome icon class based on the
     * achievement type for visual representation in the UI.
     *
     * @return string Font Awesome icon class (e.g., 'fa-star')
     */
    public function toIcon(): string
    {
        return match ($this)
        {
            self::TROOPED_ALL_SQUADS => 'fa-network-wired',
            self::TROOPER_SHIFTS => 'fa-user-plus',

            self::FIRST_TROOP => 'fa-flag-checkered',
            self::TROOPED_10 => 'fa-shield-halved',
            self::TROOPED_25 => 'fa-user-shield',
            self::TROOPED_50 => 'fa-medal',
            self::TROOPED_75 => 'fa-star-half-stroke',
            self::TROOPED_100 => 'fa-star',
            self::TROOPED_150 => 'fa-trophy',
            self::TROOPED_200 => 'fa-helmet-safety',
            self::TROOPED_250 => 'fa-award',
            self::TROOPED_300 => 'fa-certificate',
            self::TROOPED_400 => 'fa-crown',
            self::TROOPED_500 => 'fa-gem',
            self::TROOPED_501 => 'fa-brands fa-empire',

            default => 'fa-circle-question',
        };
    }

    /**
     * Get the display title for this achievement.
     *
     * Returns a Star Wars-themed title for the achievement based on its type.
     * Includes the troop count and a thematic name for milestone achievements.
     *
     * @return string Human-readable achievement title
     */
    public function toTitle(): string
    {
        return match ($this)
        {
            self::TROOPED_ALL_SQUADS => 'All Squads - Sector Sweep',
            self::TROOPER_SHIFTS => 'Total Trooper Shifts',
            self::FIRST_TROOP => '1 Troop - Mission Initiated',
            self::TROOPED_10 => '10 Troops - Outer Rim',
            self::TROOPED_25 => '25 Troops - Garrison Guard',
            self::TROOPED_50 => '50 Troops - Service Medal',
            self::TROOPED_75 => '75 Troops - Rising Star',
            self::TROOPED_100 => '100 Troops - Centurion Crest',
            self::TROOPED_150 => '150 Troops - Campaign Captain',
            self::TROOPED_200 => '200 Troops - Elite Status',
            self::TROOPED_250 => '250 Troops - Command Honor',
            self::TROOPED_300 => '300 Troops - Doctrine Seal',
            self::TROOPED_400 => '400 Troops - Core Crown',
            self::TROOPED_500 => '500 Troops - Kyber Gem',
            self::TROOPED_501 => '501 Troops - Vader\'s Fist',

            default => to_title($this->name)->toString(),
        };
    }

    /**
     * Check if this achievement is a metric (continuous statistic).
     *
     * Metrics track ongoing statistics like total shifts, hours, or funds raised.
     * They have numeric values that can increase over time.
     *
     * @return bool True if this is a metric achievement, false if it's a milestone
     */
    public function isMetric(): bool
    {
        return !$this->isMilestone();
    }

    /**
     * Check if this achievement is a milestone (one-time accomplishment).
     *
     * Milestones are boolean achievements that mark specific accomplishments,
     * such as reaching a certain number of troops or trooping with all squads.
     * Once earned, they remain earned.
     *
     * @return bool True if this is a milestone achievement, false if it's a metric
     */
    public function isMilestone(): bool
    {
        return match ($this)
        {
            self::TROOPED_ALL_SQUADS,
            self::FIRST_TROOP,
            self::TROOPED_10,
            self::TROOPED_25,
            self::TROOPED_50,
            self::TROOPED_75,
            self::TROOPED_100,
            self::TROOPED_150,
            self::TROOPED_200,
            self::TROOPED_250,
            self::TROOPED_300,
            self::TROOPED_400,
            self::TROOPED_500,
            self::TROOPED_501 => true,

            default => false,
        };
    }
}
