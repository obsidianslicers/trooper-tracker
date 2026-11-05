<?php

declare(strict_types=1);

namespace App\Messages\Plugins\PageData;

use App\Messages\Plugins\Queries\GetPlugins;
use App\Models\PluginPackage;
use Hyperdrive\Message;

/**
 * Everything the Admin > Plugins page shows: each plugin, whether administrators
 * may upload new ones, and the Composer-managed plugins they added.
 *
 * @method static array call()
 */
final class ListPluginsPageData extends Message
{
    /**
     * @return array{
     *     plugins: array<int, array<string, mixed>>,
     *     uploads: array{is_enabled: bool, max_kilobytes: int},
     *     packages: \Illuminate\Support\Collection<int, PluginPackage>
     * }
     */
    public function handle(): array
    {
        return [
            'plugins' => GetPlugins::call(),
            'uploads' => [
                'is_enabled' => (bool) config('plugins.uploads.enabled', false),
                'max_kilobytes' => (int) config('plugins.uploads.max_kilobytes', 10240),
            ],
            'packages' => PluginPackage::query()->orderBy(PluginPackage::NAME)->get(),
        ];
    }
}
