<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Commands;

use App\Enums\PackageUpdatePolicy;
use App\Models\PluginPackage;
use App\Plugins\PluginDeployPackages;
use Hyperdrive\Message;

/**
 * Saves a Composer-managed plugin for the next deploy to install. Nothing is
 * installed now: Composer never runs from a web request.
 *
 * @method static PluginPackage call(
 *     string $name,
 *     ?string $version_constraint = null,
 *     ?string $repository_url = null,
 *     PackageUpdatePolicy $update_policy = PackageUpdatePolicy::PINNED,
 * )
 */
final class AddPluginPackage extends Message
{
    public function __construct(
        private readonly string $name,
        private readonly ?string $version_constraint = null,
        private readonly ?string $repository_url = null,
        private readonly PackageUpdatePolicy $update_policy = PackageUpdatePolicy::PINNED,
    ) {}

    public function handle(PluginDeployPackages $packages): PluginPackage
    {
        $package = PluginPackage::firstOrNew([PluginPackage::NAME => strtolower($this->name)]);
        $package->version_constraint = $this->version_constraint ?: null;
        $package->repository_url = $this->repository_url ?: null;
        $package->update_policy = $this->update_policy->value;
        $package->save();

        $packages->writeRequirements();

        return $package;
    }
}
