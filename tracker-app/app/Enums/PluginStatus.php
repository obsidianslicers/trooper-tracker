<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What the last deploy, or the last change on the Plugins page, found for a plugin.
 */
enum PluginStatus: string
{
    use HasEnumHelpers;

    case ACTIVE = 'active';
    case DISABLED = 'disabled';
    case PENDING = 'pending';
    case FAILED = 'failed';
    case MISSING = 'missing';
}
