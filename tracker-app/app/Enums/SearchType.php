<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Defines available global search filters.
 */
enum SearchType: string
{
    use HasEnumHelpers;

    case ALL = 'all';
    case TROOPERS = 'troopers';
    case EVENTS = 'events';
}
