<?php

declare(strict_types=1);

namespace App\Plugins;

/**
 * The version of the hooks and contracts plugins build on. A plugin states the
 * range it works with as "trooptracker" in its plugin.json "requires".
 *
 * Raise the minor version when hooks are added, and the major version when a
 * hook or contract changes in a way that can break existing plugins.
 */
final class PluginApi
{
    public const VERSION = '1.0.0';

    public const REQUIREMENT = 'trooptracker';
}
