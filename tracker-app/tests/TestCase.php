<?php

namespace Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected string $plugin_deploy_path = '';

    protected function setUp(): void
    {
        parent::setUp();

        // Tells Laravel to ignore missing Vite assets
        $this->withoutVite();

        // Keep plugin deploy files out of the real storage folder.
        $this->plugin_deploy_path = sys_get_temp_dir().'/tt-plugin-deploy-'.uniqid();
        config(['plugins.deploy_path' => $this->plugin_deploy_path]);
    }

    protected function skipIfSqlite(): void
    {
        if (config('database.default') === 'sqlite')
        {
            $this->markTestSkipped('Requires MySQL JSON functions (JSON_CONTAINS / JSON_OVERLAPS).');
        }
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->plugin_deploy_path);

        parent::tearDown();

        // echo memory_get_usage() . PHP_EOL;

        gc_collect_cycles();
    }
}
