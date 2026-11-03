<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Commands;

use App\Plugins\PluginManager;
use Hyperdrive\Message;

/**
 * Turns a plugin off and deletes its data: rolls back its migrations and removes
 * its settings. The plugin's files stay until the next deploy removes them.
 *
 * @method static void call(string $slug)
 */
final class UninstallPlugin extends Message
{
    public function __construct(private readonly string $slug) {}

    public function handle(PluginManager $plugins): void
    {
        $plugins->uninstall($this->slug);
    }
}
