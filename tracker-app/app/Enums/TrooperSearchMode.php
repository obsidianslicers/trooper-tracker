<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Trooper search modes.
 *
 * Defines the various modes for selecting troopers in search/dropdown components,
 * including no specific filtering and selecting friends.
 */
enum TrooperSearchMode: string
{
    /**
     * Search mode for selecting troopers with no specific filtering.
     */
    case NONE = 'none';

    /**
     * Search mode for selecting troopers based on their participation in events.
     * Defaults to a recent list of friends who they have added as friends in the system.
     */
    case FRIENDS = 'friends';

    /**
     * Search mode for selecting troopers based on their participation in events.
     * Defaults to a recent list of friends who they have added as friends in the system.
     */
    case MODERATED = 'moderated';

    /**
     * Wide open search mode for administrative selection of troopers.
     */
    case ADMIN = 'admin';
}
