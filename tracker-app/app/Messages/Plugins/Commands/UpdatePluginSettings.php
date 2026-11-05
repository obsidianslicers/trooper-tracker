<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Commands;

use App\Models\Plugin;
use App\Plugins\PluginManager;
use Hyperdrive\Message;

/**
 * Saves the admin-editable settings a plugin declares in its plugin.json.
 *
 * @method static Plugin call(string $slug, array $settings)
 */
final class UpdatePluginSettings extends Message
{
    /**
     * @param  array<string, mixed>  $settings
     */
    public function __construct(
        private readonly string $slug,
        private readonly array $settings,
    ) {}

    public function handle(PluginManager $plugins): Plugin
    {
        return $plugins->updateSettings($this->slug, $this->settings);
    }
}
