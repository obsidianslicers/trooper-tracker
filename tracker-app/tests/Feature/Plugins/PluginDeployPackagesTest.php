<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use App\Messages\Plugins\Commands\EnablePlugin;
use App\Models\Trooper;
use App\Plugins\PluginAutoloader;
use App\Plugins\PluginDeployPackages;
use App\Plugins\PluginDiscovery;
use App\Plugins\PluginManager;
use App\Plugins\PluginRequirementsException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use stdClass;
use Tests\TestCase;

class PluginDeployPackagesTest extends TestCase
{
    use RefreshDatabase;

    private string $plugins_path;

    private PluginManager $plugins;

    private PluginDeployPackages $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugins_path = sys_get_temp_dir().'/tt-plugin-packages-'.uniqid();
        $this->useBundledPlugins($this->plugins_path);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->plugins_path);

        parent::tearDown();
    }

    public function test_enabling_a_plugin_missing_only_its_packages_waits_for_the_deploy(): void
    {
        $this->writePlugin('sheets', ['acme/not-installed' => '^1.0']);

        $record = EnablePlugin::call(slug: 'sheets');

        $this->assertTrue($record->is_enable_pending);
        $this->assertFalse($this->plugins->isEnabled('sheets'));
        $this->assertTrue($this->plugins->isEnablePending('sheets'));
        $this->assertSame(['acme/sheets'], $this->subject->toInstall());
    }

    public function test_the_deploy_lists_enabled_plugins_with_packages_only(): void
    {
        $this->writePlugin('installed', ['laravel/framework' => '*']);
        $this->writePlugin('off', ['laravel/framework' => '*']);
        $this->writePlugin('no-packages', ['php' => '>=8.2', 'ext-json' => '*']);
        $this->plugins->enable('installed');
        $this->plugins->enable('no-packages');

        $this->assertSame(['acme/installed'], $this->subject->toInstall());
        $this->artisan('plugins:packages')->expectsOutput('acme/installed')->assertSuccessful();
    }

    public function test_enable_pending_turns_the_plugin_on_once_its_packages_are_in(): void
    {
        $this->writePlugin('sheets', ['acme/not-installed' => '^1.0']);
        EnablePlugin::call(slug: 'sheets');

        $this->artisan('plugins:enable-pending')
            ->expectsOutputToContain('is still waiting')
            ->assertFailed();
        $this->assertTrue($this->plugins->isEnablePending('sheets'));

        $this->writePlugin('sheets', ['laravel/framework' => '*']);
        $this->artisan('plugins:enable-pending')
            ->expectsOutput('Plugin [sheets] enabled.')
            ->assertSuccessful();

        $this->assertTrue($this->plugins->isEnabled('sheets'));
        $this->assertFalse($this->plugins->isEnablePending('sheets'));
    }

    public function test_other_missing_requirements_still_refuse_the_enable(): void
    {
        $this->writePlugin('sheets', ['acme/not-installed' => '^1.0'], ['php' => '>=99.0']);

        $this->expectException(PluginRequirementsException::class);

        EnablePlugin::call(slug: 'sheets');
    }

    public function test_disabling_cancels_a_waiting_plugin(): void
    {
        $this->writePlugin('sheets', ['acme/not-installed' => '^1.0']);
        EnablePlugin::call(slug: 'sheets');

        $this->plugins->disable('sheets');

        $this->assertFalse($this->plugins->isEnablePending('sheets'));
        $this->assertSame([], $this->subject->toInstall());
    }

    public function test_the_plugins_page_shows_a_waiting_plugin(): void
    {
        $this->writePlugin('sheets', ['acme/not-installed' => '^1.0']);
        $trooper = Trooper::factory()->asAdministrator()->create();

        $this->actingAs($trooper)->get(route('admin.plugins.list'))
            ->assertSee('are installed by the next deploy after you enable it');

        $this->actingAs($trooper)
            ->post(route('admin.plugins.enable', ['slug' => 'sheets']))
            ->assertSessionHas('info');

        $this->actingAs($trooper)->get(route('admin.plugins.list'))
            ->assertSee('Turns on at next deploy')
            ->assertSee('Cancel');
    }

    public function test_an_uploaded_plugin_is_never_installed_by_the_deploy(): void
    {
        $uploads = $this->plugins_path.'-uploads';
        $this->useBundledPlugins($this->plugins_path, $uploads);
        $this->writePlugin('sheets', ['acme/not-installed' => '^1.0'], [], $uploads);

        try
        {
            $this->expectException(PluginRequirementsException::class);

            EnablePlugin::call(slug: 'sheets');
        }
        finally
        {
            (new Filesystem)->deleteDirectory($uploads);
        }
    }

    private function useBundledPlugins(string $local_path, ?string $uploads_path = null): void
    {
        $discovery = new PluginDiscovery(
            $local_path,
            $uploads_path,
            includes_composer_packages: false,
        );
        $this->plugins = new PluginManager(
            $this->app,
            $discovery,
            $this->app->make(PluginAutoloader::class),
        );

        $this->app->instance(PluginDiscovery::class, $discovery);
        $this->app->instance(PluginManager::class, $this->plugins);
        $this->subject = $this->app->make(PluginDeployPackages::class);
    }

    /**
     * @param  array<string, string>  $packages  composer.json require
     * @param  array<string, string>  $requires  plugin.json requires
     */
    private function writePlugin(
        string $slug,
        array $packages,
        array $requires = [],
        ?string $folder = null,
    ): void {
        $namespace = 'Packages'.str_replace('-', '', ucwords($slug, '-')).'Plugin';
        $path = ($folder ?? $this->plugins_path)."/{$slug}";

        (new Filesystem)->ensureDirectoryExists($path.'/src');
        file_put_contents($path.'/plugin.json', (string) json_encode([
            'slug' => $slug,
            'name' => ucfirst($slug),
            'namespace' => $namespace,
            'provider' => "{$namespace}\\Provider",
            'requires' => $requires === [] ? new stdClass : $requires,
        ]));
        file_put_contents($path.'/composer.json', (string) json_encode([
            'name' => "acme/{$slug}",
            'require' => $packages,
        ]));
        file_put_contents($path.'/src/Provider.php', "<?php\n\nnamespace {$namespace};\n\n"
            ."class Provider extends \\App\\Plugins\\PluginServiceProvider {}\n");

        $this->plugins->forgetManifests();
    }
}
