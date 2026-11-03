<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use App\Messages\Plugins\Queries\GetPlugins;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetPluginsTest extends TestCase
{
    use RefreshDatabase;
    use UsesFixturePlugins;

    public function test_call_reports_where_each_plugin_came_from(): void
    {
        $uploads_path = sys_get_temp_dir().'/tt-plugin-uploads-'.uniqid();
        $files = new Filesystem;
        $files->copyDirectory(base_path('tests/Fixtures/plugins/hello'), $uploads_path.'/uploaded');
        $files->put($uploads_path.'/uploaded/plugin.json', (string) json_encode([
            'slug' => 'uploaded',
            'namespace' => 'UploadedPlugin',
            'provider' => 'UploadedPlugin\\UploadedServiceProvider',
        ]));
        $this->useFixturePlugins($uploads_path)->enable('hello');

        $plugins = collect(GetPlugins::call())->keyBy(fn (array $plugin): string => $plugin['manifest']->slug);

        $this->assertSame('local', $plugins['hello']['source']);
        $this->assertTrue($plugins['hello']['is_enabled']);
        $this->assertSame('uploaded', $plugins['uploaded']['source']);
        $this->assertFalse($plugins['uploaded']['is_installed']);

        $files->deleteDirectory($uploads_path);
    }
}
