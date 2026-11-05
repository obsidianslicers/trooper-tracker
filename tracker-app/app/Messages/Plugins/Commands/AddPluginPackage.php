<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Commands;

use App\Models\PluginPackage;
use Hyperdrive\Message;

/**
 * Saves a Composer-managed plugin for the next deploy to install. Nothing is
 * installed now: Composer never runs from a web request.
 *
 * @method static PluginPackage call(string $name, ?string $version_constraint = null)
 */
final class AddPluginPackage extends Message
{
    public function __construct(
        private readonly string $name,
        private readonly ?string $version_constraint = null,
    ) {}

    public function handle(): PluginPackage
    {
        $package = PluginPackage::firstOrNew([PluginPackage::NAME => strtolower($this->name)]);
        $package->version_constraint = $this->version_constraint ?: null;
        $package->save();

        return $package;
    }
}
