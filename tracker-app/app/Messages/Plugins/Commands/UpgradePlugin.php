<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Commands;

use App\Models\Plugin;
use App\Plugins\PluginManager;
use Hyperdrive\Message;

/**
 * Brings an enabled plugin's database up to the version of its files, keeping its
 * settings and data. Run after a deploy brings new plugin files.
 *
 * @method static Plugin call(string $slug)
 */
final class UpgradePlugin extends Message
{
    public function __construct(private readonly string $slug) {}

    public function handle(PluginManager $plugins): Plugin
    {
        return $plugins->upgrade($this->slug);
    }
}
