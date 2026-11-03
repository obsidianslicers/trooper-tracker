<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Commands;

use App\Models\Plugin;
use App\Plugins\PluginManager;
use Hyperdrive\Message;

/**
 * Turns a plugin on for the whole site, running its migrations first.
 *
 * @method static Plugin call(string $slug)
 */
final class EnablePlugin extends Message
{
    public function __construct(private readonly string $slug) {}

    public function handle(PluginManager $plugins): Plugin
    {
        return $plugins->enable($this->slug);
    }
}
