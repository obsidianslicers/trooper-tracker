<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Resources;

use App\Models\Plugin;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A plugin on the Admin > Plugins page that the database knows but whose files are gone, with
 * what was last recorded about it.
 *
 * @mixin Plugin
 */
class MissingPluginResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Plugin::SLUG => $this->slug,
            'details' => collect([
                $this->version,
                $this->source !== null ? ucfirst($this->source) : null,
                $this->is_enabled ? __('admin.was enabled') : __('admin.was disabled'),
            ])->filter()->implode(' · '),
            Plugin::LAST_ERROR => filled($this->last_error) ? $this->last_error : null,
        ];
    }
}
