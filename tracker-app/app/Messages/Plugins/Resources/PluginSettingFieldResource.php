<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Resources;

use App\Plugins\PluginManifest;
use App\Plugins\PluginSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A field on the Admin > Plugins settings page, as the plugin declares it, with its saved value.
 * A saved secret never leaves the server: the field is blank and only says one is set.
 *
 * @property PluginSetting $resource
 */
class PluginSettingFieldResource extends JsonResource
{
    public function __construct(
        PluginSetting $setting,
        private readonly PluginManifest $manifest,
        private readonly mixed $saved_value,
    ) {
        parent::__construct($setting);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $setting = $this->resource;

        return [
            'key' => $setting->key,
            'type' => $setting->type,
            'label' => $this->manifest->translate($setting->label),
            'help' => $setting->help !== '' ? $this->manifest->translate($setting->help) : null,
            'value' => $this->formValue(),
            'is_saved' => $setting->type === 'secret' && filled($this->saved_value),
        ];
    }

    private function formValue(): string
    {
        return match ($this->resource->type)
        {
            'secret' => '',
            'boolean' => $this->saved_value ? '1' : '0',
            default => (string) ($this->saved_value ?? ''),
        };
    }
}
