<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo;

use App\Plugins\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use TroopTracker\Plugins\Xenforo\Console\SyncUserCommand;
use TroopTracker\Plugins\Xenforo\Console\SyncUsersCommand;

class XenforoServiceProvider extends PluginServiceProvider
{
    /**
     * Settings saved on the plugin's page win over the matching .env values.
     */
    private const SETTING_CONFIG = [
        'base_url' => 'services.xenforo.base_url',
        'name' => 'services.xenforo.name',
        'api_key' => 'services.xenforo.api_key',
        'api_user' => 'services.xenforo.api_user',
        'client_id' => 'services.xenforo.client_id',
        'client_secret' => 'services.xenforo.client_secret',
        'redirect' => 'services.xenforo.redirect',
        'webhook_secret' => 'services.xenforo.webhook_secret',
        'require_xenforo' => 'tracker.require_xenforo',
    ];

    protected function registerPlugin(): void
    {
        $this->applySettingsToConfig();
    }

    public function bootPlugin(): void
    {
        $this->commands([SyncUserCommand::class, SyncUsersCommand::class]);

        $this->schedule(function (Schedule $schedule): void
        {
            $schedule->command(SyncUsersCommand::class)->hourly();
        });

        $this->addSystemCheck(
            'XenForo API',
            fn (): bool => filled(config('services.xenforo.base_url'))
                && filled(config('services.xenforo.api_key')),
        );
    }

    private function applySettingsToConfig(): void
    {
        foreach (self::SETTING_CONFIG as $setting => $config_key)
        {
            $value = $this->setting($setting);

            if ($value !== null && $value !== '')
            {
                config([$config_key => $setting === 'base_url' ? rtrim($value, '/') : $value]);
            }
        }
    }
}
