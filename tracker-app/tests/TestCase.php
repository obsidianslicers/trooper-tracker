<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use ReflectionClass;

abstract class TestCase extends BaseTestCase
{
    protected function invokeMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new ReflectionClass(get_class($object));

        $method = $reflection->getMethod($methodName);

        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('debugbar.enabled', false);
        app()->forgetInstance('debugbar');
        ini_set('memory_limit', '512M'); // or -1 for unlimited
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // echo memory_get_usage() . PHP_EOL;

        gc_collect_cycles();
    }
}
