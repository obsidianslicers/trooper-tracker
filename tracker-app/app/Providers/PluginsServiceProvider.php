<?php

declare(strict_types=1);

namespace App\Providers;

use App\Plugins\PluginAutoloader;
use App\Plugins\PluginDiscovery;
use App\Plugins\PluginForms;
use App\Plugins\PluginManager;
use App\Plugins\PluginSlots;
use App\Plugins\PluginSystemChecks;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Wires up the plugin system and loads every enabled plugin.
 *
 * @see docs/PLUGINS.md
 */
class PluginsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginAutoloader::class);
        $this->app->singleton(PluginSlots::class);
        $this->app->singleton(PluginForms::class);
        $this->app->singleton(PluginSystemChecks::class);

        $this->app->singleton(PluginDiscovery::class, fn (): PluginDiscovery => new PluginDiscovery(
            (string) config('plugins.path', base_path('plugins'))
        ));

        $this->app->singleton(PluginManager::class);

        // Deferred services such as the cache are only available once every provider
        // has registered, so plugins load just before the providers boot.
        $this->app->booting(fn () => $this->app->make(PluginManager::class)->loadEnabledPlugins());
    }

    public function boot(): void
    {
        Blade::directive('pluginSlot', fn (string $expression): string => '<?php echo app('
            .PluginSlots::class."::class)->render({$expression}, get_defined_vars()); ?>");

        Blade::if('pluginEnabled', fn (string $slug): bool => $this->app
            ->make(PluginManager::class)
            ->isEnabled($slug));
    }
}
