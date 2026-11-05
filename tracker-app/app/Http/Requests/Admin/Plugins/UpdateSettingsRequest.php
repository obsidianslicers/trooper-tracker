<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Plugins;

use App\Plugins\PluginManager;
use App\Plugins\PluginManifest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a plugin's settings form against the settings its plugin.json declares.
 */
class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = ['settings' => ['nullable', 'array']];

        foreach ($this->manifest()->settings as $setting)
        {
            $rules["settings.{$setting->key}"] = $setting->rules();
        }

        return $rules;
    }

    public function manifest(): PluginManifest
    {
        return app(PluginManager::class)->find((string) $this->route('slug')) ?? abort(404);
    }
}
