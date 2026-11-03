<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo;

use App\Plugins\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use TroopTracker\Plugins\Xenforo\Console\SyncUserCommand;
use TroopTracker\Plugins\Xenforo\Console\SyncUsersCommand;
use TroopTracker\Plugins\Xenforo\Http\Controllers\AccountLinkController;
use TroopTracker\Plugins\Xenforo\Http\Controllers\AccountLinkRequiredController;
use TroopTracker\Plugins\Xenforo\Http\Middleware\CheckXenforoBan;
use TroopTracker\Plugins\Xenforo\Http\Middleware\EnsureXenforoLinked;
use TroopTracker\Plugins\Xenforo\Socialite\XenforoProvider;

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
        'require_xenforo' => 'tracker.auth.require_xenforo',
    ];

    protected function registerPlugin(): void
    {
        $this->applySettingsToConfig();
    }

    public function bootPlugin(): void
    {
        $this->commands([SyncUserCommand::class, SyncUsersCommand::class]);

        Socialite::extend('xenforo', fn (): XenforoProvider => Socialite::buildProvider(
            XenforoProvider::class,
            config('services.xenforo'),
        ));

        $this->addWebMiddleware(EnsureXenforoLinked::class);
        $this->addWebMiddleware(CheckXenforoBan::class);
        $this->registerAccountRoutes();

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

    /**
     * Kept at their old /account/xenforo addresses and account.xenforo.* names,
     * which core's login and setup flow still link to.
     */
    private function registerAccountRoutes(): void
    {
        Route::middleware(['web', 'auth', 'redirect.denied', 'redirect.pending'])
            ->prefix('account/xenforo')
            ->name('account.xenforo.')
            ->group(function (): void
            {
                Route::get('/', AccountLinkController::class)->name('index');
                Route::get('/required', AccountLinkRequiredController::class)->name('required');
            });
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
