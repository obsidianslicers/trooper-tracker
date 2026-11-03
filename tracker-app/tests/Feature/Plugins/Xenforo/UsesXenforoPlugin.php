<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\Xenforo;

use App\Plugins\PluginManager;
use Illuminate\Contracts\Console\Kernel;

trait UsesXenforoPlugin
{
    protected function enableXenforo(): PluginManager
    {
        $plugins = $this->app->make(PluginManager::class);
        $plugins->enable('xenforo');
        $plugins->load($plugins->findOrFail('xenforo'));

        // Rebuild Artisan so the plugin's commands are registered, as in a fresh process.
        $this->app->make(Kernel::class)->setArtisan(null);

        return $plugins;
    }
}
