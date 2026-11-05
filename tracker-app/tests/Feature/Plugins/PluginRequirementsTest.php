<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use App\Enums\PluginChangeAction;
use App\Models\PluginChange;
use App\Models\Trooper;
use App\Plugins\PluginManager;
use App\Plugins\PluginRequirementsException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use stdClass;
use Tests\TestCase;

class PluginRequirementsTest extends TestCase
{
    use RefreshDatabase;
    use UsesFixturePlugins;

    private string $plugins_path;

    private PluginManager $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugins_path = sys_get_temp_dir().'/tt-plugin-requirements-'.uniqid();
        $this->subject = $this->useFixturePlugins($this->plugins_path);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->plugins_path);

        parent::tearDown();
    }

    public function test_enable_refuses_a_plugin_that_needs_a_newer_php(): void
    {
        $this->writePlugin('needs-php', ['php' => '>=99.0']);

        try
        {
            $this->subject->enable('needs-php');
            $this->fail('The plugin should not have been enabled.');
        }
        catch (PluginRequirementsException $e)
        {
            $this->assertStringContainsString("Needs Php can't be turned on yet.", $e->getMessage());
            $this->assertStringContainsString('PHP >=99.0', $e->getMessage());
        }

        $this->assertFalse($this->subject->isEnabled('needs-php'));
        $this->assertDatabaseHas('tt_plugin_changes', [
            'slug' => 'needs-php',
            'action' => PluginChangeAction::FAILED->value,
        ]);
    }

    public function test_enable_checks_the_plugin_folders_composer_json(): void
    {
        $this->writePlugin('needs-package');
        file_put_contents(
            $this->plugins_path.'/needs-package/composer.json',
            (string) json_encode(['require' => ['acme/not-installed' => '^1.0']]),
        );

        $this->assertSame(
            ['the Composer package acme/not-installed ^1.0'],
            $this->subject->unmetRequirements('needs-package'),
        );
        $this->expectException(PluginRequirementsException::class);

        $this->subject->enable('needs-package');
    }

    public function test_enable_refuses_a_plugin_for_another_plugin_api(): void
    {
        $this->writePlugin('old-api', ['trooptracker' => '^0.1']);

        $this->expectException(PluginRequirementsException::class);

        $this->subject->enable('old-api');
    }

    public function test_enable_needs_the_required_plugin_turned_on_first(): void
    {
        $this->writePlugin('base');
        $this->writePlugin('addon', ['plugins' => ['base' => '^1.0']]);

        $this->assertSame(
            ['the Base plugin turned on first'],
            $this->subject->unmetRequirements('addon'),
        );

        $this->subject->enable('base');
        $this->subject->enable('addon');

        $this->assertTrue($this->subject->isEnabled('addon'));
    }

    public function test_enable_reports_a_required_plugin_that_is_missing_or_too_old(): void
    {
        $this->writePlugin('base', version: '1.0.0');
        $this->writePlugin('addon', ['plugins' => ['base' => '^2.0', 'gone' => '*']]);

        $this->assertSame([
            'the Base plugin ^2.0 (1.0.0 is installed)',
            'the [gone] plugin *, which is not installed',
        ], $this->subject->unmetRequirements('addon'));
    }

    public function test_disable_and_uninstall_refuse_a_plugin_another_still_needs(): void
    {
        $this->writePlugin('base');
        $this->writePlugin('addon', ['plugins' => ['base' => '^1.0']]);
        $this->subject->enable('base');
        $this->subject->enable('addon');

        foreach (['disable', 'uninstall'] as $action)
        {
            try
            {
                $this->subject->{$action}('base');
                $this->fail("{$action} should have been refused.");
            }
            catch (PluginRequirementsException $e)
            {
                $this->assertSame(
                    'Base is still needed by Addon. Turn those off first.',
                    $e->getMessage(),
                );
            }
        }

        $this->assertTrue($this->subject->isEnabled('base'));
        $this->subject->disable('addon');
        $this->subject->disable('base');
        $this->assertFalse($this->subject->isEnabled('base'));
    }

    public function test_loading_skips_an_enabled_plugin_whose_requirement_went_away(): void
    {
        $this->writePlugin('base');
        $this->writePlugin('addon', ['plugins' => ['base' => '^1.0']]);
        $this->subject->enable('base');
        $this->subject->enable('addon');
        PluginChange::query()->delete();
        $this->writePlugin('base', version: '3.0.0');

        $manager = $this->useFixturePlugins($this->plugins_path);
        $manager->loadEnabledPlugins();

        $this->assertTrue($manager->isLoaded('base'));
        $this->assertFalse($manager->isLoaded('addon'));
    }

    public function test_the_list_shows_what_is_missing_and_disables_enable(): void
    {
        $this->writePlugin('needs-php', ['php' => '>=99.0']);
        $trooper = Trooper::factory()->asAdministrator()->create();

        $this->actingAs($trooper)->get(route('admin.plugins.list'))
            ->assertSee('Needs PHP &gt;=99.0', false)
            ->assertSee('disabled', false);
    }

    public function test_the_enable_page_shows_the_reason_it_was_refused(): void
    {
        $this->writePlugin('needs-php', ['php' => '>=99.0']);
        $trooper = Trooper::factory()->asAdministrator()->create();

        $this->actingAs($trooper)
            ->post(route('admin.plugins.enable', ['slug' => 'needs-php']))
            ->assertRedirect(route('admin.plugins.list'))
            ->assertSessionHas('danger');
    }

    public function test_the_enable_command_shows_the_reason_it_was_refused(): void
    {
        $this->writePlugin('needs-php', ['php' => '>=99.0']);

        $this->artisan('plugins:enable', ['slug' => 'needs-php'])
            ->expectsOutputToContain('PHP >=99.0')
            ->assertFailed();
    }

    /**
     * @param  array<string, mixed>  $requires
     */
    private function writePlugin(string $slug, array $requires = [], string $version = '1.0.0'): void
    {
        $name = ucwords(str_replace('-', ' ', $slug));
        $namespace = 'Requirements'.str_replace(' ', '', $name).'Plugin';
        $path = "{$this->plugins_path}/{$slug}";

        (new Filesystem)->ensureDirectoryExists($path.'/src');
        file_put_contents($path.'/plugin.json', (string) json_encode([
            'slug' => $slug,
            'name' => $name,
            'version' => $version,
            'namespace' => $namespace,
            'provider' => "{$namespace}\\Provider",
            'requires' => $requires === [] ? new stdClass : $requires,
        ]));
        file_put_contents($path.'/src/Provider.php', "<?php\n\nnamespace {$namespace};\n\n"
            ."class Provider extends \\App\\Plugins\\PluginServiceProvider {}\n");

        $this->subject->forgetManifests();
    }
}
