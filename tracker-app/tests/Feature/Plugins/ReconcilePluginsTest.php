<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use App\Enums\PluginChangeAction;
use App\Enums\PluginSource;
use App\Enums\PluginStatus;
use App\Messages\Plugins\Commands\EnablePlugin;
use App\Messages\Plugins\Commands\ReconcilePlugins;
use App\Models\Plugin;
use App\Models\PluginChange;
use App\Models\Trooper;
use App\Plugins\PluginAutoloader;
use App\Plugins\PluginDiscovery;
use App\Plugins\PluginManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;
use Tests\TestCase;

/**
 * Each deploy() builds a fresh plugin manager, the way a new release boots, then
 * runs the reconcile step deploy.sh runs after Composer and migrations.
 */
class ReconcilePluginsTest extends TestCase
{
    use BuildsPluginArchives;
    use RefreshDatabase;

    private const ITEMS = "Schema::create('tt_keeper_items', fn (\$t) => \$t->id());";

    private const ITEMS_DOWN = "Schema::dropIfExists('tt_keeper_items');";

    private const LABEL = "Schema::table('tt_keeper_items', "
        ."fn (\$t) => \$t->string('label')->nullable());";

    private const LABEL_DOWN = "Schema::table('tt_keeper_items', "
        ."fn (\$t) => \$t->dropColumn('label'));";

    private const ITEMS_MIGRATION = '000001_items';

    private const ITEMS_UP_DOWN = [self::ITEMS, self::ITEMS_DOWN];

    private string $bundled_path;

    private string $uploads_path;

    private PluginManager $plugins;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bundled_path = sys_get_temp_dir().'/tt-reconcile-bundled-'.uniqid();
        $this->uploads_path = sys_get_temp_dir().'/tt-reconcile-uploads-'.uniqid();
        (new Filesystem)->ensureDirectoryExists($this->bundled_path);
        (new Filesystem)->ensureDirectoryExists($this->uploads_path);
        config(['plugins.uploads.enabled' => true]);
        $this->boot();
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->bundled_path);
        (new Filesystem)->deleteDirectory($this->uploads_path);

        parent::tearDown();
    }

    public function test_uploaded_plugins_survive_repeated_deploys(): void
    {
        $this->actingAs(Trooper::factory()->asAdministrator()->create())
            ->post(route('admin.plugins.install'), [
                'archive' => $this->pluginArchive($this->greeterFiles()),
            ])
            ->assertSessionHas('success');
        $this->plugins->enable('greeter');

        $this->deploy();
        $report = $this->deploy();

        $this->assertSame([], $report);
        $this->assertTrue($this->plugins->isEnabled('greeter'));
        $this->assertFileExists($this->uploads_path.'/greeter/plugin.json');
        $this->assertRecord('greeter', PluginSource::UPLOADED, PluginStatus::ACTIVE);
    }

    public function test_custom_plugins_copied_to_the_server_survive_deploys(): void
    {
        $this->writePlugin('keeper', folder: $this->uploads_path);
        $this->plugins->enable('keeper');
        $this->plugins->updateSettings('keeper', ['greeting' => 'Hi there']);

        $this->deploy();
        $this->deploy();

        $this->assertTrue($this->plugins->isEnabled('keeper'));
        $this->assertSame('Hi there', $this->plugins->setting('keeper', 'greeting'));
        $this->assertRecord('keeper', PluginSource::CUSTOM, PluginStatus::ACTIVE);
    }

    public function test_disabled_plugins_stay_disabled(): void
    {
        $this->writePlugin('keeper');
        $this->plugins->enable('keeper');
        $this->plugins->disable('keeper');

        $this->deploy();
        $this->deploy();

        $this->assertFalse($this->plugins->isEnabled('keeper'));
        $this->assertRecord('keeper', PluginSource::BUNDLED, PluginStatus::DISABLED);
    }

    public function test_waiting_plugins_turn_on_once_their_packages_are_in(): void
    {
        $this->writePlugin('keeper', packages: ['acme/not-installed' => '^1.0']);
        EnablePlugin::call(slug: 'keeper');

        $this->deploy();
        $this->deploy();

        $this->assertTrue($this->plugins->isEnablePending('keeper'));
        $this->assertRecord('keeper', PluginSource::BUNDLED, PluginStatus::PENDING);
        $this->assertStringContainsString(
            'acme/not-installed',
            (string) $this->record('keeper')->last_error,
        );
        $this->assertSame(0, $this->changes('keeper', PluginChangeAction::FAILED));

        $this->writePlugin('keeper', packages: ['laravel/framework' => '*']);
        $report = $this->deploy();

        $this->assertSame('turned on', $report['keeper']);
        $this->assertTrue($this->plugins->isEnabled('keeper'));
        $this->assertRecord('keeper', PluginSource::BUNDLED, PluginStatus::ACTIVE);
        $this->assertNull($this->record('keeper')->last_error);
    }

    public function test_a_failed_package_install_keeps_the_plugin_files_and_settings(): void
    {
        $this->writePlugin('keeper', packages: ['laravel/framework' => '*']);
        $this->plugins->enable('keeper');
        $this->plugins->updateSettings('keeper', ['greeting' => 'Kept']);
        $this->writePlugin('keeper', packages: ['acme/lost' => '^1.0']);
        $this->composerResult(['ok' => false, 'error' => 'Could not reach the repository.']);

        $report = $this->deploy();

        $this->assertArrayHasKey('keeper', $report);
        $this->assertTrue($this->plugins->isEnabled('keeper'));
        $this->assertSame('Kept', $this->plugins->setting('keeper', 'greeting'));
        $this->assertFileExists($this->bundled_path.'/keeper/plugin.json');
        $this->assertRecord('keeper', PluginSource::BUNDLED, PluginStatus::FAILED);
        $this->assertStringContainsString(
            'Could not reach the repository.',
            (string) $this->record('keeper')->last_error,
        );
    }

    public function test_missing_plugins_keep_their_settings_and_come_back(): void
    {
        $this->writePlugin('keeper');
        $this->plugins->enable('keeper');
        $this->plugins->updateSettings('keeper', ['greeting' => 'Kept']);
        $folder = $this->bundled_path.'/keeper';
        $moved = $this->uploads_path.'-moved';
        (new Filesystem)->moveDirectory($folder, $moved);

        $first = $this->deploy();
        $second = $this->deploy();

        $this->assertArrayHasKey('keeper', $first);
        $this->assertSame([], $second);
        $this->assertSame(PluginStatus::MISSING->value, $this->record('keeper')->status);
        $this->assertSame(['greeting' => 'Kept'], $this->record('keeper')->settings);

        $this->actingAs(Trooper::factory()->asAdministrator()->create())
            ->get(route('admin.plugins.list'))
            ->assertSee('Missing plugins')
            ->assertSee('keeper');

        (new Filesystem)->moveDirectory($moved, $folder);
        $this->deploy();

        $this->assertRecord('keeper', PluginSource::BUNDLED, PluginStatus::ACTIVE);
        $this->assertSame('Kept', $this->plugins->setting('keeper', 'greeting'));
    }

    public function test_updates_keep_the_plugin_data(): void
    {
        $this->writePlugin('keeper', migrations: [self::ITEMS_MIGRATION => self::ITEMS_UP_DOWN]);
        $this->plugins->enable('keeper');
        DB::table('tt_keeper_items')->insert(['id' => 7]);

        $this->writePlugin('keeper', '2.0.0', migrations: [
            self::ITEMS_MIGRATION => self::ITEMS_UP_DOWN,
            '000002_label' => [self::LABEL, self::LABEL_DOWN],
        ]);
        $report = $this->deploy();

        $this->assertSame('upgraded to 2.0.0', $report['keeper']);
        $this->assertSame(7, (int) DB::table('tt_keeper_items')->value('id'));
        $this->assertTrue(Schema::hasColumn('tt_keeper_items', 'label'));
        $this->assertSame('2.0.0', $this->record('keeper')->installed_version);
        $this->assertTrue($this->plugins->isEnabled('keeper'));
    }

    public function test_repeated_deploys_leave_unchanged_plugins_alone(): void
    {
        $this->writePlugin('keeper', migrations: [self::ITEMS_MIGRATION => self::ITEMS_UP_DOWN]);
        $this->plugins->enable('keeper');
        $this->deploy();
        $updated_at = $this->record('keeper')->updated_at;
        $changes = PluginChange::query()->count();

        $this->travel(5)->minutes();
        $this->artisan('plugins:reconcile')
            ->expectsOutput('Every plugin is as it was.')
            ->assertSuccessful();

        $this->assertEquals($updated_at, $this->record('keeper')->updated_at);
        $this->assertSame($changes, PluginChange::query()->count());
        $this->assertTrue(Schema::hasTable('tt_keeper_items'));
    }

    /**
     * @return array<string, string>
     */
    private function deploy(): array
    {
        $this->boot();

        return ReconcilePlugins::call();
    }

    private function boot(): void
    {
        $discovery = new PluginDiscovery(
            $this->bundled_path,
            $this->uploads_path,
            includes_composer_packages: false,
        );
        $this->plugins = new PluginManager(
            $this->app,
            $discovery,
            $this->app->make(PluginAutoloader::class),
        );

        config(['plugins.uploads.path' => $this->uploads_path]);
        $this->app->instance(PluginDiscovery::class, $discovery);
        $this->app->instance(PluginManager::class, $this->plugins);
    }

    private function record(string $slug): Plugin
    {
        return Plugin::query()->where(Plugin::SLUG, $slug)->firstOrFail();
    }

    private function assertRecord(string $slug, PluginSource $source, PluginStatus $status): void
    {
        $record = $this->record($slug);

        $this->assertSame($source->value, $record->source);
        $this->assertSame($status->value, $record->status);
    }

    private function changes(string $slug, PluginChangeAction $action): int
    {
        return PluginChange::query()
            ->where(PluginChange::SLUG, $slug)
            ->where(PluginChange::ACTION, $action->value)
            ->count();
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function composerResult(array $result): void
    {
        (new Filesystem)->ensureDirectoryExists($this->plugin_deploy_path);
        file_put_contents(
            $this->plugin_deploy_path.'/composer-result.json',
            (string) json_encode($result),
        );
    }

    /**
     * @param  array<string, string>  $packages  composer.json require
     * @param  array<string, array{0: string, 1: string}>  $migrations  name => [up, down]
     */
    private function writePlugin(
        string $slug,
        string $version = '1.0.0',
        array $packages = [],
        array $migrations = [],
        ?string $folder = null,
    ): void {
        $namespace = 'Reconcile'.ucfirst($slug).'Plugin';
        $path = ($folder ?? $this->bundled_path)."/{$slug}";
        $files = new Filesystem;

        $files->ensureDirectoryExists($path.'/src');
        $files->deleteDirectory($path.'/database');
        file_put_contents($path.'/plugin.json', (string) json_encode([
            'slug' => $slug,
            'name' => ucfirst($slug),
            'version' => $version,
            'namespace' => $namespace,
            'provider' => "{$namespace}\\Provider",
            'requires' => new stdClass,
            'settings' => [['key' => 'greeting', 'label' => 'Greeting', 'type' => 'text']],
        ]));
        file_put_contents($path.'/src/Provider.php', "<?php\n\nnamespace {$namespace};\n\n"
            ."class Provider extends \\App\\Plugins\\PluginServiceProvider {}\n");

        if ($packages !== [])
        {
            file_put_contents($path.'/composer.json', (string) json_encode([
                'name' => "acme/{$slug}",
                'require' => $packages,
            ]));
        }

        foreach ($migrations as $name => [$up, $down])
        {
            $files->ensureDirectoryExists($path.'/database/migrations');
            file_put_contents(
                $path."/database/migrations/2026_01_01_{$name}.php",
                "<?php\n\nuse Illuminate\\Support\\Facades\\Schema;\n\n"
                ."return new class extends Illuminate\\Database\\Migrations\\Migration {\n"
                ."    public function up(): void { {$up} }\n"
                ."    public function down(): void { {$down} }\n"
                ."};\n",
            );
        }

        $this->plugins->forgetManifests();
    }
}
