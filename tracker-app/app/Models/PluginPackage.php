<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Base\PluginPackage as BasePluginPackage;
use App\Models\Concerns\HasTrooperStamps;
use Composer\InstalledVersions;

/**
 * A Composer-managed plugin an administrator added, for the deploy to require.
 */
class PluginPackage extends BasePluginPackage
{
    use HasTrooperStamps;

    /**
     * What the deploy passes to "composer require", e.g. "acme/badges:^1.0".
     */
    public function requirement(): string
    {
        return $this->version_constraint === null
            ? $this->name
            : "{$this->name}:{$this->version_constraint}";
    }

    public function isInstalled(): bool
    {
        return InstalledVersions::isInstalled($this->name);
    }
}
