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
     */
    protected function useFixturePlugins(): PluginManager
    {
        $manager = new PluginManager(
            $this->app,
            new PluginDiscovery(base_path('tests/Fixtures/plugins')),
            $this->app->make(PluginAutoloader::class),
        );

        $this->app->instance(PluginManager::class, $manager);

        return $manager;
    }
}
