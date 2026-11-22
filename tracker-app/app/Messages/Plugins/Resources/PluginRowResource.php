<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Resources;

use App\Plugins\PluginManifest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A row of the Plugins page, from one entry of `GetPlugins`: what it is, its state in words,
 * and which buttons it gets.
 *
 * @property array<string, mixed> $resource
 */
class PluginRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PluginManifest $manifest */
        $manifest = $this->resource['manifest'];
        $plugin = $this->resource;
        $has_capabilities = $manifest->capabilities !== [];
        $capabilities = $has_capabilities
            ? __('admin.It :capabilities.', ['capabilities' => $plugin['capability_summary']])
            : null;

        return [
            'slug' => $manifest->slug,
            'name' => $manifest->name,
            'description' => $manifest->description !== ''
                ? $manifest->translate($manifest->description)
                : null,
            'author' => $manifest->author !== ''
                ? __('admin.By :author', ['author' => $manifest->author])
                : null,
            'capabilities' => $capabilities,
            'version' => $manifest->version,
            'pending_upgrade' => $plugin['has_pending_upgrade']
                ? __('admin.Update pending from :version', ['version' => $plugin['installed_version']])
                : null,
            'source' => ucfirst((string) $plugin['source']),
            'status' => $this->status($plugin),
            'requirements' => $this->requirements($plugin),
            'last_error' => filled($plugin['last_error'])
                ? __('admin.Last deploy: :error', ['error' => $plugin['last_error']])
                : null,
            'has_settings' => $manifest->hasSettings(),
            'toggle' => $this->toggle($plugin),
            'can_enable' => $plugin['unmet_requirements'] === [] || $plugin['can_install_on_deploy'],
            'enable_confirm' => $has_capabilities
                ? __('admin.Turn on :plugin?', ['plugin' => $manifest->name]).' '.$capabilities
                : null,
            'is_installed' => $plugin['is_installed'],
        ];
    }

    /**
     * @param  array<string, mixed>  $plugin
     * @return array{label: string, tone: string, title: string|null}
     */
    private function status(array $plugin): array
    {
        [$label, $tone, $title] = match (true)
        {
            $plugin['is_enabled'] && $plugin['status'] === 'failed' => [
                __('admin.Needs attention'), 'warning',
                __('admin.Enabled, but the last deploy could not finish setting it up.'),
            ],
            $plugin['is_enabled'] && $plugin['is_loaded'] => [__('admin.Enabled'), 'success', null],
            $plugin['is_enabled'] => [
                __('admin.Failed to load'), 'danger',
                __('admin.Enabled, but it failed to load. Check the logs.'),
            ],
            $plugin['is_enable_pending'] => [
                __('admin.Turns on at next deploy'), 'info',
                __('admin.The next deploy installs its packages and turns it on.'),
            ],
            default => [__('admin.Disabled'), 'secondary', null],
        };

        return ['label' => $label, 'tone' => $tone, 'title' => $title];
    }

    /**
     * @param  array<string, mixed>  $plugin
     * @return array{text: string, is_problem: bool}|null
     */
    private function requirements(array $plugin): ?array
    {
        $unmet = implode('; ', $plugin['unmet_requirements']);

        return match (true)
        {
            (bool) $plugin['is_enable_pending'] => null,
            (bool) $plugin['can_install_on_deploy'] => [
                'text' => __(
                    'admin.Its packages (:packages) are installed by the next deploy after you enable it.',
                    ['packages' => $unmet]
                ),
                'is_problem' => false,
            ],
            $plugin['unmet_requirements'] !== [] => [
                'text' => __('admin.Needs :requirements.', ['requirements' => $unmet]),
                'is_problem' => true,
            ],
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $plugin
     */
    private function toggle(array $plugin): string
    {
        return match (true)
        {
            (bool) $plugin['is_enabled'] => 'disable',
            (bool) $plugin['is_enable_pending'] => 'cancel',
            default => 'enable',
        };
    }
}
