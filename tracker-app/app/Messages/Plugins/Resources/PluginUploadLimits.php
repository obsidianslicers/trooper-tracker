<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Whether the Admin > Plugins page offers to upload a plugin, and how big the archive may be.
 * Read from the plugins config.
 */
class PluginUploadLimits extends JsonResource
{
    public function __construct()
    {
        parent::__construct(config('plugins.uploads', []));
    }

    /**
     * @return array{is_enabled: bool, max_megabytes: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'is_enabled' => (bool) config('plugins.uploads.enabled', true),
            'max_megabytes' => format_number(config('plugins.uploads.max_kilobytes', 10240) / 1024, 1),
        ];
    }
}
