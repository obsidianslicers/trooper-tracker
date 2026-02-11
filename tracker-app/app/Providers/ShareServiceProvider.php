<?php

declare(strict_types=1);

namespace App\Providers;

use App\Facades\Share;
use App\Facades\ShareFacade;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class ShareServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * Binds the Share class to the service container and registers
     * the ShareFacade alias.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(Share::class, function ()
        {
            return new Share;
        });

        AliasLoader::getInstance()->alias('Share', ShareFacade::class);
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
    }
}
