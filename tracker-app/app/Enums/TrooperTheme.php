<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Represents the available UI themes for a trooper's profile or interface.
 *
 * This enum provides a type-safe way to manage and select different visual
 * themes within the application.
 */
enum TrooperTheme: string
{
    use HasEnumHelpers;

    /**
     * A theme based on the classic Imperial Stormtrooper.
     */
    case STORMTROOPER = 'stormtrooper';
    /**
     * A theme based on the Republic's Clone Troopers.
     */
    case CLONE = 'clone';
    /**
     * A theme based on the galaxy's Bounty Hunters.
     */
    case BOUNTY_HUNTER = 'bountyhunter';
    /**
     * A theme based on the Rebel Alliance.
     */
    case REBEL = 'rebel';
    /**
     * A theme based on the dark side's Sith Lords.
     */
    case SITH = 'sith';
}