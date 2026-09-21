<?php

declare(strict_types=1);

use Chaincode\Commands\TraceCommand;
use Illuminate\Support\ServiceProvider;

class ChainCodeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole())
        {
            $this->commands([
                TraceCommand::class,
            ]);
        }
    }
}