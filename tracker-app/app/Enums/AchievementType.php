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
     * Total number of events the trooper has participated in
     */
    case TROOPER_EVENTS = 'trooper_events';

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
}
