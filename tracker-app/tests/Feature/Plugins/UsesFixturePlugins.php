<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use App\Plugins\PluginAutoloader;
use App\Plugins\PluginDiscovery;
use App\Plugins\PluginManager;

trait UsesFixturePlugins
{
    /**
     * Point the plugin manager at tests/Fixtures/plugins instead of the real plugins folder.
     * Installed Composer plugins are left out too, so the tests don't depend on them.
     */
    protected function useFixturePlugins(?string $uploads_path = null): PluginManager
    {
        $discovery = new PluginDiscovery(
            base_path('tests/Fixtures/plugins'),
            $uploads_path,
            includes_composer_packages: false,
        );

        $manager = new PluginManager(
            $this->app,
            $discovery,
            $this->app->make(PluginAutoloader::class),
        );

        $this->app->instance(PluginDiscovery::class, $discovery);
        $this->app->instance(PluginManager::class, $manager);

        return $manager;
    }
}
