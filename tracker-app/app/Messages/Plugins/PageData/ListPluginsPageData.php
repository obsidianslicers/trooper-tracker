<?php

declare(strict_types=1);

namespace App\Messages\Plugins\PageData;

use App\Messages\Plugins\Queries\GetPlugins;
use App\Messages\Plugins\Resources\PluginRowResource;
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
        return [
            'plugins' => PluginRowResource::collection(GetPlugins::call()),
            'uploads' => [
                'is_enabled' => (bool) config('plugins.uploads.enabled', true),
                'max_megabytes' => format_number(config('plugins.uploads.max_kilobytes', 10240) / 1024, 1),
            ],
            'missing' => Plugin::query()
                ->orderBy(Plugin::SLUG)
                ->get()
                ->filter(fn (Plugin $record): bool => $plugins->find($record->slug) === null)
                ->map(fn (Plugin $record): array => [
                    'slug' => $record->slug,
                    'details' => collect([
                        $record->version,
                        $record->source !== null ? ucfirst($record->source) : null,
                        $record->is_enabled ? __('admin.was enabled') : __('admin.was disabled'),
                    ])->filter()->implode(' · '),
                    'last_error' => filled($record->last_error) ? $record->last_error : null,
                ])
                ->values(),
        ];
    }
}
