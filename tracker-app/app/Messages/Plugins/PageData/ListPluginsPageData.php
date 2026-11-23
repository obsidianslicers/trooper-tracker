<?php

declare(strict_types=1);

namespace App\Messages\Plugins\PageData;

use App\Messages\Plugins\Queries\GetPlugins;
use App\Messages\Plugins\Resources\MissingPluginResource;
use App\Messages\Plugins\Resources\PluginRowResource;
use App\Messages\Plugins\Resources\PluginUploadLimits;
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
     * @return array<string, mixed>
     */
    public function handle(PluginManager $plugins): array
    {
        $missing = Plugin::query()
            ->orderBy(Plugin::SLUG)
            ->get()
            ->filter(fn (Plugin $record): bool => $plugins->find($record->slug) === null)
            ->values();

        return [
            'plugins' => PluginRowResource::collection(GetPlugins::call()),
            'uploads' => new PluginUploadLimits,
            'missing' => MissingPluginResource::collection($missing),
        ];
    }
}
