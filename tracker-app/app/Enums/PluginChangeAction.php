<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Something that happened to a plugin, as kept in its change history.
 */
enum PluginChangeAction: string
{
    use HasEnumHelpers;

    case INSTALLED = 'installed';
    case UPGRADED = 'upgraded';
    case ENABLED = 'enabled';
    case DISABLED = 'disabled';
    case UNINSTALLED = 'uninstalled';
    case ROLLED_BACK = 'rolled_back';
    case FAILED = 'failed';
}
