<?php

declare(strict_types=1);

namespace App\Messages\Plugins\PageData;

use App\Enums\PackageUpdatePolicy;
use App\Messages\Plugins\Queries\GetPlugins;
use App\Models\Plugin;
use App\Models\PluginPackage;
use App\Plugins\PluginManager;
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
     *     packages: \Illuminate\Support\Collection<int, PluginPackage>,
     *     missing: \Illuminate\Support\Collection<int, Plugin>,
     *     update_policies: array<string, string>
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
            'packages' => PluginPackage::query()->orderBy(PluginPackage::NAME)->get(),
            'missing' => Plugin::query()
                ->orderBy(Plugin::SLUG)
                ->get()
                ->filter(fn (Plugin $record): bool => $plugins->find($record->slug) === null)
                ->values(),
            'update_policies' => array_combine(
                array_column(PackageUpdatePolicy::cases(), 'value'),
                array_map(
                    fn (PackageUpdatePolicy $policy): string => $policy->description(),
                    PackageUpdatePolicy::cases(),
                ),
            ),
        ];
    }
}
