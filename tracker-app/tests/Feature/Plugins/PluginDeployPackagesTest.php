<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use App\Enums\PluginSource;
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
        $this->assertSame(['acme/not-installed:^1.0'], $this->subject->toInstall());
    }

    public function test_the_deploy_lists_enabled_plugins_with_packages_only(): void
    {
        $this->writePlugin('installed', ['laravel/framework' => '*']);
        $this->writePlugin('off', ['laravel/framework' => '*']);
        $this->writePlugin('no-packages', ['php' => '>=8.2', 'ext-json' => '*']);
        $this->plugins->enable('installed');
        $this->plugins->enable('no-packages');

        $this->assertSame(['laravel/framework'], $this->subject->toInstall());
        $this->artisan('plugins:packages')->expectsOutput('laravel/framework')->assertSuccessful();
    }

    public function test_enable_pending_turns_the_plugin_on_once_its_packages_are_in(): void
    {
        $this->writePlugin('sheets', ['acme/not-installed' => '^1.0']);
        EnablePlugin::call(slug: 'sheets');

        $this->assertStringContainsString(
            'acme/not-installed',
            (string) $this->subject->enablePending()['sheets'],
        );
        $this->assertTrue($this->plugins->isEnablePending('sheets'));

        $this->writePlugin('sheets', ['laravel/framework' => '*']);
        $this->assertSame(['sheets' => null], $this->subject->enablePending());

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
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $this->actingAs($trooper)->get(route('admin.plugins.list'))
            ->assertSee('are installed by the next deploy after you enable it');

        $this->actingAs($trooper)
            ->post(route('admin.plugins.enable', ['slug' => 'sheets']))
            ->assertSessionHas('info');

        $this->actingAs($trooper)->get(route('admin.plugins.list'))
            ->assertSee('Turns on at next deploy')
            ->assertSee('Cancel');
    }

    public function test_plugins_from_every_folder_get_their_packages_from_the_deploy(): void
    {
        $uploads = $this->plugins_path.'-uploads';
        $this->useBundledPlugins($this->plugins_path, $uploads);
        $this->writePlugin('sheets', ['acme/sheets-api' => '^1.0']);
        $this->writePlugin('uploaded', ['acme/uploaded-api' => '^2.0'], [], $uploads);
        $this->writePlugin('copied', ['acme/sheets-api' => '^1.2'], [], $uploads);
        $this->plugins->recordSource('uploaded', PluginSource::UPLOADED);

        try
        {
            EnablePlugin::call(slug: 'sheets');
            EnablePlugin::call(slug: 'uploaded');
            EnablePlugin::call(slug: 'copied');

            $this->assertTrue($this->plugins->isEnablePending('uploaded'));
            $this->assertSame([
                'packages' => ['acme/sheets-api' => '^1.2 ^1.0', 'acme/uploaded-api' => '^2.0'],
            ], $this->saved());
        }
        finally
        {
            (new Filesystem)->deleteDirectory($uploads);
        }
    }

    public function test_disabling_a_plugin_drops_its_packages_from_the_next_deploy(): void
    {
        $this->writePlugin('sheets', ['laravel/framework' => '*']);
        $this->plugins->enable('sheets');
        $this->assertSame(['laravel/framework'], $this->subject->toInstall());

        $this->plugins->disable('sheets');

        $this->assertSame([], $this->subject->toInstall());
        $this->assertSame(
            [],
            $this->saved()['packages'],
        );
    }

    public function test_the_saved_requirements_stay_writable_by_the_deploy_user(): void
    {
        if (DIRECTORY_SEPARATOR === '\\')
        {
            $this->markTestSkipped('File permissions are not reliably reported on Windows.');
        }

        $this->writePlugin('sheets', ['laravel/framework' => '*']);
        $this->plugins->enable('sheets');

        $path = PluginDeployPackages::requirementsPath();

        $this->assertSame(0664, fileperms($path) & 0777);
        $this->assertSame(02775, fileperms(dirname($path)) & 07777);
    }

    /**
     * @return array<string, mixed>
     */
    private function saved(): array
    {
        $path = PluginDeployPackages::requirementsPath();

        return json_decode((string) file_get_contents($path), true);
    }

    private function useBundledPlugins(string $local_path, ?string $uploads_path = null): void
    {
        $discovery = new PluginDiscovery(
            $local_path,
            $uploads_path,
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
