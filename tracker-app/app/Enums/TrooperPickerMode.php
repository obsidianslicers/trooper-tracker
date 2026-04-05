<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Trooper picker modes.
 *
 * Defines the various modes for selecting troopers in picker/dropdown components,
 * including no specific filtering and selecting friends.
 */
enum TrooperPickerMode: string
{
    /**
     * Picker mode for selecting troopers with no specific filtering.
     */
    case NONE = 'none';

    /**
     * Picker mode for selecting troopers based on their participation in events.
     * Defaults to a recent list of friends who they have added as friends in the system.
     */
    case FRIENDS = 'friends';
}
