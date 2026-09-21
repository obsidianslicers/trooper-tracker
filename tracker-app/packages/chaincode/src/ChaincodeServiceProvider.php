<?php

declare(strict_types=1);

namespace Chaincode;

use Chaincode\Commands\TraceCommand;
use Illuminate\Support\ServiceProvider;

class ChaincodeServiceProvider extends ServiceProvider
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