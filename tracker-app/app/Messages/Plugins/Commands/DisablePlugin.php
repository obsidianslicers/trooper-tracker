<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Commands;

use App\Models\Plugin;
use App\Plugins\PluginManager;
use Hyperdrive\Message;

/**
 * Turns a plugin off for the whole site. Its data and settings are kept.
 *
 * @method static Plugin call(string $slug)
 */
final class DisablePlugin extends Message
{
    public function __construct(private readonly string $slug) {}

    public function handle(PluginManager $plugins): Plugin
    {
        return $plugins->disable($this->slug);
    }
}
