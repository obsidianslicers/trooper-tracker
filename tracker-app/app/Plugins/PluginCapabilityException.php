<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Enums\PluginCapability;
use LogicException;

/**
 * A plugin used a hook its plugin.json does not list under "capabilities".
 */
final class PluginCapabilityException extends LogicException
{
    public static function notDeclared(PluginManifest $manifest, PluginCapability $capability): self
    {
        return new self("Plugin [{$manifest->slug}] {$capability->description()}, but its "
            ."plugin.json does not list \"{$capability->value}\" under \"capabilities\".");
    }
}
