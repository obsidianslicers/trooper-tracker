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
     * Total months in which the trooper has made a donation
     */
    case DONATION_MONTHS = 'donation_months';

    /**
     * Milestone for 12+ months of donations (1 year)
     */
    case SUPPORTER_12_MONTHS = 'supporter_12_months';

    /**
     * Milestone for 24+ months of donations (2 years)
     */
    case SUPPORTER_24_MONTHS = 'supporter_24_months';

    /**
     * Milestone for 36+ months of donations (3 years)
     */
    case SUPPORTER_36_MONTHS = 'supporter_36_months';

    /**
     * Milestone for 60+ months of donations (5 years)
     */
    case SUPPORTER_60_MONTHS = 'supporter_60_months';

    /**
     * Milestone for $100 cumulative donated
     */
    case DONATED_100 = 'donated_100';

    /**
     * Milestone for $250 cumulative donated
     */
    case DONATED_250 = 'donated_250';

    /**
     * Milestone for $500 cumulative donated
     */
    case DONATED_500 = 'donated_500';

    /**
     * Milestone for $1,000 cumulative donated
     */
    case DONATED_1000 = 'donated_1000';

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
            self::INDIRECT_FUNDS,
            self::DONATION_MONTHS => 'number',

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

            self::FIRST_TROOP => 'fa-file-shield',      // Combat Readiness Citation
            self::TROOPED_10 => 'fa-ribbon',           // Frontier Service Ribbon
            self::TROOPED_25 => 'fa-shield-halved',     // Garrison Duty Excellence Medal
            self::TROOPED_50 => 'fa-medal',            // Imperial Medal of Valor
            self::TROOPED_75 => 'fa-award',            // Distinguished Service Cross
            self::TROOPED_100 => 'fa-grip-lines',       // Centurion Legionary Bar (The "Bar")
            self::TROOPED_150 => 'fa-star',             // Campaign Heroism Star
            self::TROOPED_200 => 'fa-feather-pointed',  // Order of the Imperial Wing
            self::TROOPED_250 => 'fa-id-badge',         // High Command Merit Badge
            self::TROOPED_300 => 'fa-certificate',      // Grand Admiral\'s Citation
            self::TROOPED_400 => 'fa-crown',            // Medal of the Emperor\'s Will
            self::TROOPED_500 => 'fa-trophy',           // Honor of the Galactic Empire
            self::TROOPED_501 => 'fa-brands fa-empire', // Vader\'s Fist

            self::DONATION_MONTHS => 'fa-hand-holding-heart',
            self::SUPPORTER_12_MONTHS => 'fa-calendar-check',    // 1 Year of Support
            self::SUPPORTER_24_MONTHS => 'fa-calendar-days',     // 2 Years of Support
            self::SUPPORTER_36_MONTHS => 'fa-calendar',          // 3 Years of Support
            self::SUPPORTER_60_MONTHS => 'fa-star-of-life',      // 5 Years of Support

            self::DONATED_100  => 'fa-coins',
            self::DONATED_250  => 'fa-sack-dollar',
            self::DONATED_500  => 'fa-vault',
            self::DONATED_1000 => 'fa-gem',

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

            self::FIRST_TROOP => 'Combat Readiness Citation',
            self::TROOPED_10 => 'Frontier Service Ribbon',
            self::TROOPED_25 => 'Garrison Duty Excellence Medal',
            self::TROOPED_50 => 'Imperial Medal of Valor',
            self::TROOPED_75 => 'Distinguished Service Cross',
            self::TROOPED_100 => 'Centurion Legionary Bar',
            self::TROOPED_150 => 'Campaign Heroism Star',
            self::TROOPED_200 => 'Order of the Imperial Wing',
            self::TROOPED_250 => 'High Command Merit Badge',
            self::TROOPED_300 => 'Grand Admiral\'s Citation',
            self::TROOPED_400 => 'Medal of the Emperor\'s Will',
            self::TROOPED_500 => 'Honor of the Galactic Empire',
            self::TROOPED_501 => 'Vader\'s Fist - Legion of Honor',

            self::DONATION_MONTHS   => 'Total Donation Months',
            self::SUPPORTER_12_MONTHS => '1 Year Supporter',
            self::SUPPORTER_24_MONTHS => '2 Year Supporter',
            self::SUPPORTER_36_MONTHS => '3 Year Supporter',
            self::SUPPORTER_60_MONTHS => '5 Year Supporter',

            self::DONATED_100  => 'Quartermaster\'s Commendation',
            self::DONATED_250  => 'Supply Corps Citation',
            self::DONATED_500  => 'Imperial Benefactor Medal',
            self::DONATED_1000 => 'Grand Patron of the Empire',

            default => to_title($this->name)->toString(),
        };
    }

    /**
     * Get the display title for this achievement.
     *
     * Returns a Star Wars-themed title for the achievement based on its type.
     * Includes the troop count and a thematic name for milestone achievements.
     *
     * @return string Human-readable achievement description
     */
    public function toDescription(): string
    {
        return match ($this)
        {
            self::TROOPED_ALL_SQUADS => 'All Squads - Sector Sweep',
            self::TROOPER_SHIFTS => 'Total Trooper Shifts',

            self::FIRST_TROOP => 'First Troop - Combat Readiness Citation',
            self::TROOPED_10 => '10 Troops - Frontier Service Ribbon',
            self::TROOPED_25 => '25 Troops - Garrison Duty Excellence Medal',
            self::TROOPED_50 => '50 Troops - Imperial Medal of Valor',
            self::TROOPED_75 => '75 Troops - Distinguished Service Cross',
            self::TROOPED_100 => '100 Troops - Centurion Legionary Bar',
            self::TROOPED_150 => '150 Troops - Campaign Heroism Star',
            self::TROOPED_200 => '200 Troops - Order of the Imperial Wing',
            self::TROOPED_250 => '250 Troops - High Command Merit Badge',
            self::TROOPED_300 => '300 Troops - Grand Admiral\'s Citation',
            self::TROOPED_400 => '400 Troops - Medal of the Emperor\'s Will',
            self::TROOPED_500 => '500 Troops - Honor of the Galactic Empire',
            self::TROOPED_501 => '501 Troops - Vader\'s Fist - Legion of Honor',

            self::DONATION_MONTHS   => 'Total months with an active donation',
            self::SUPPORTER_12_MONTHS => '12 Months - 1 Year Supporter',
            self::SUPPORTER_24_MONTHS => '24 Months - 2 Year Supporter',
            self::SUPPORTER_36_MONTHS => '36 Months - 3 Year Supporter',
            self::SUPPORTER_60_MONTHS => '60 Months - 5 Year Supporter',

            self::DONATED_100  => '$100 Donated - Quartermaster\'s Commendation',
            self::DONATED_250  => '$250 Donated - Supply Corps Citation',
            self::DONATED_500  => '$500 Donated - Imperial Benefactor Medal',
            self::DONATED_1000 => '$1,000 Donated - Grand Patron of the Empire',

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
            self::TROOPED_501,
            self::SUPPORTER_12_MONTHS,
            self::SUPPORTER_24_MONTHS,
            self::SUPPORTER_36_MONTHS,
            self::SUPPORTER_60_MONTHS,
            self::DONATED_100,
            self::DONATED_250,
            self::DONATED_500,
            self::DONATED_1000 => true,

            default => false,
        };
    }
}
