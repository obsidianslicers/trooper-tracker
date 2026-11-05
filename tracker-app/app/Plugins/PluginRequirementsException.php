<?php

declare(strict_types=1);

namespace App\Plugins;

/**
 * A plugin needs something the site does not have, or another plugin still needs it.
 */
final class PluginRequirementsException extends PluginException
{
    /**
     * @param  array<int, string>  $unmet
     */
    public static function cannotEnable(PluginManifest $manifest, array $unmet): self
    {
        return new self("{$manifest->name} can't be turned on yet. It needs "
            .implode('; ', $unmet).'.');
    }

    /**
     * @param  array<int, string>  $dependents  names of enabled plugins that need it
     */
    public static function stillNeeded(PluginManifest $manifest, array $dependents): self
    {
        return new self("{$manifest->name} is still needed by "
            .implode(', ', $dependents).'. Turn those off first.');
    }
}
