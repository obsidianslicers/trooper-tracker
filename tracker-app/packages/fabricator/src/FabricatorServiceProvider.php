<?php

namespace Fabricator;

use Illuminate\Support\ServiceProvider;
use Fabricator\Commands\FabricateFactoryCommand;

class FabricatorServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Register the command if we're running in the console
        if ($this->app->runningInConsole())
        {
            $this->commands([
                FabricateFactoryCommand::class,
            ]);
        }
    }
}