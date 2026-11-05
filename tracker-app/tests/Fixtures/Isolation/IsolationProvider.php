<?php

declare(strict_types=1);

namespace Tests\Fixtures\Isolation;

use App\Plugins\PluginManifest;
use App\Plugins\PluginServiceProvider;
use Closure;
use Illuminate\Contracts\Foundation\Application;

/**
 * Exposes the provider hooks so tests can register plugin code directly.
 */
class IsolationProvider extends PluginServiceProvider
{
    public function __construct(Application $app)
    {
        parent::__construct($app, PluginManifest::fromArray([
            'slug' => 'isolation',
            'provider' => self::class,
            'namespace' => 'Tests\\Fixtures\\Isolation',
        ], base_path('tests/Fixtures/Isolation'), false));
    }

    public function listenTo(string $event, Closure|string|array $listener): void
    {
        $this->listen($event, $listener);
    }

    public function addMiddleware(string $middleware_class): void
    {
        $this->addWebMiddleware($middleware_class);
    }
}
