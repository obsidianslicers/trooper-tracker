<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Resources;

use App\Plugins\PluginManifest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The plugin whose settings the Admin > Plugins settings page edits.
 *
 * @property PluginManifest $resource
 */
class PluginSettingsPluginResource extends JsonResource
{
    /**
     * @return array{slug: string, name: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->resource->slug,
            'name' => $this->resource->name,
        ];
    }
}
