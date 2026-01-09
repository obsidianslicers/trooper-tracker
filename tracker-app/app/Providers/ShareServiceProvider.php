<?php

namespace App\Providers;

use App\Facades\Share;
use App\Facades\ShareFacade;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class ShareServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->bind(Share::class, function ()
        {
            return new Share();
        });

        AliasLoader::getInstance()->alias('Share', ShareFacade::class);
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
    }
}
