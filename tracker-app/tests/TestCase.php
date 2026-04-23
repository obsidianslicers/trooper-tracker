<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        // Tells Laravel to ignore missing Vite assets
        $this->withoutVite();
    }

    protected function skipIfSqlite(): void
    {
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Requires MySQL JSON functions (JSON_CONTAINS / JSON_OVERLAPS).');
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // echo memory_get_usage() . PHP_EOL;

        gc_collect_cycles();
    }
}
