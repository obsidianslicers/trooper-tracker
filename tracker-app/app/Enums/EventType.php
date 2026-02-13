<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Defines the types of events (troops) that can be organized.
 *
 * Event types categorize different kinds of costuming appearances and activities.
 */
enum EventType: string
{
    use HasEnumHelpers;

    /**
     * Standard costuming event
     */
    case REGULAR = 'regular';

    /**
     * Social gathering for armor building and maintenance
     */
    case ARMOR_PARTY = 'armorparty';

    /**
     * Charitable fundraising event
     */
    case CHARITY = 'charity';

    /**
     * Public relations or promotional appearance
     */
    case PUBLIC_RELATIONS = 'publicrelations';

    /**
     * Disney-related event or appearance
     */
    case DISNEY = 'disney';

    /**
     * Lucasfilm/official Star Wars event
     */
    case LUCAS_FILM_LIMITED = 'lucasfilm';

    /**
     * Convention or expo appearance
     */
    case CONVENTION = 'convention';

    /**
     * Hospital visit or patient interaction
     */
    case HOSPITAL = 'hospital';

    /**
     * Private wedding appearance
     */
    case WEDDING = 'wedding';

    /**
     * Private birthday party appearance
     */
    case BIRTHDAY_PARTY = 'birthdayparty';

    /**
     * Online or remote virtual event
     */
    case VIRTUAL_TROOP = 'virtualtroop';

    /**
     * Other type of event not listed
     */
    case OTHER = 'other';
}
