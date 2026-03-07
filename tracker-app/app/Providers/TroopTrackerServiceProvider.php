<?php

declare(strict_types=1);

namespace App\Providers;

use App\Facades\TroopTracker;
use App\Facades\TroopTrackerFacade;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class TroopTrackerServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * Binds the TroopTracker class to the service container and registers
     * the TroopTrackerFacade alias.
     */
    public function register(): void
    {
        $this->app->bind(TroopTracker::class, function ()
        {
            return new TroopTracker;
        });

        AliasLoader::getInstance()->alias('TroopTracker', TroopTrackerFacade::class);

        $this->app->bind(TroopTracker::class, function ()
        {
            return new TroopTracker();
        });

        AliasLoader::getInstance()->alias('TroopTracker', TroopTrackerFacade::class);
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
    }
}
