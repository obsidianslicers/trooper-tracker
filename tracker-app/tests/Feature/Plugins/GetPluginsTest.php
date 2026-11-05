<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use App\Enums\PluginSource;
use App\Messages\Plugins\Queries\GetPlugins;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
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
        $manager = $this->useFixturePlugins($uploads_path);
        $manager->enable('hello');

        $plugins = $this->pluginsBySlug();

        $this->assertSame('bundled', $plugins['hello']['source']);
        $this->assertTrue($plugins['hello']['is_enabled']);
        $this->assertSame('custom', $plugins['uploaded']['source']);
        $this->assertFalse($plugins['uploaded']['is_installed']);

        $manager->recordSource('uploaded', PluginSource::UPLOADED);

        $this->assertSame('uploaded', $this->pluginsBySlug()['uploaded']['source']);

        $files->deleteDirectory($uploads_path);
    }

    /**
     * @return Collection<string, array<string, mixed>>
     */
    private function pluginsBySlug(): Collection
    {
        return collect(GetPlugins::call())
            ->keyBy(fn (array $plugin): string => $plugin['manifest']->slug);
    }
}
