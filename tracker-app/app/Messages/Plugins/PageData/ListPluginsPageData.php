<?php

declare(strict_types=1);

namespace App\Messages\Plugins\PageData;

use App\Messages\Plugins\Queries\GetPlugins;
use App\Models\Plugin;
use App\Plugins\PluginManager;
use Hyperdrive\Message;

/**
 * Everything the Admin > Plugins page shows: each plugin, whether administrators
 * may upload new ones, and plugins whose files are missing.
 *
 * @method static array call()
 */
final class ListPluginsPageData extends Message
{
    /**
     * @return array{
     *     plugins: array<int, array<string, mixed>>,
     *     uploads: array{is_enabled: bool, max_kilobytes: int},
     *     missing: \Illuminate\Support\Collection<int, Plugin>
     * }
     */
    public function handle(PluginManager $plugins): array
    {
        return [
            'plugins' => GetPlugins::call(),
            'uploads' => [
                'is_enabled' => (bool) config('plugins.uploads.enabled', true),
                'max_kilobytes' => (int) config('plugins.uploads.max_kilobytes', 10240),
            ],
            'missing' => Plugin::query()
                ->orderBy(Plugin::SLUG)
                ->get()
                ->filter(fn (Plugin $record): bool => $plugins->find($record->slug) === null)
                ->values(),
        ];
    }
}
