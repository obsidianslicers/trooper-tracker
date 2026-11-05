<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use App\Enums\PluginChangeAction;
use App\Models\PluginChange;
use App\Models\Trooper;
use App\Plugins\PluginManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PluginUpgradeTest extends TestCase
{
    use BuildsPluginArchives;
    use RefreshDatabase;
    use UsesFixturePlugins;

    private const ITEMS = "Schema::create('tt_greeter_items', function (\$table) {"
        ."\$table->id(); \$table->string('name'); });";

    private const COLOR = "Schema::table('tt_greeter_items', function (\$table) {"
        ."\$table->string('color')->nullable(); });";

    private const EXTRAS = "Schema::create('tt_greeter_extras', function (\$table) {"
        .'$table->id(); });';

    private string $uploads_path;

    private PluginManager $plugins;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploads_path = sys_get_temp_dir().'/tt-plugin-upgrades-'.uniqid();
        $this->plugins = $this->useFixturePlugins($this->uploads_path);
        config(['plugins.uploads.enabled' => true]);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->uploads_path);

        parent::tearDown();
    }

    public function test_upgrading_an_enabled_plugin_keeps_its_data_and_settings(): void
    {
        $this->installAndEnableVersionOne();
        DB::table('tt_greeter_items')->insert(['name' => 'kept']);

        $this->upload('2.0.0', ['000002_add_color' => self::COLOR])
            ->assertSessionHas('success');

        $this->assertSame('kept', DB::table('tt_greeter_items')->value('name'));
        $this->assertTrue(Schema::hasColumn('tt_greeter_items', 'color'));
        $this->assertTrue($this->plugins->isEnabled('greeter'));
        $this->assertSame('2.0.0', $this->plugins->record('greeter')->installed_version);
        $this->assertSame('2.0.0', $this->plugins->findOrFail('greeter')->version);
        $this->assertDirectoryDoesNotExist($this->uploads_path.'/.previous/greeter');
        $this->assertTrue($this->hasChange(PluginChangeAction::UPGRADED, '1.0.0', '2.0.0'));
    }

    public function test_a_failed_upgrade_rolls_back_and_puts_the_old_files_back(): void
    {
        $this->installAndEnableVersionOne();
        DB::table('tt_greeter_items')->insert(['name' => 'kept']);

        $this->upload('2.0.0', [
            '000002_create_extras' => self::EXTRAS,
            '000003_broken' => "throw new RuntimeException('boom');",
        ])->assertSessionHasErrors('archive');

        $this->assertFalse(Schema::hasTable('tt_greeter_extras'));
        $this->assertSame('kept', DB::table('tt_greeter_items')->value('name'));
        $this->assertSame('1.0.0', $this->plugins->findOrFail('greeter')->version);
        $this->assertSame('1.0.0', $this->plugins->record('greeter')->installed_version);
        $this->assertTrue($this->plugins->isEnabled('greeter'));
        $this->assertTrue($this->hasChange(PluginChangeAction::FAILED, '1.0.0', '2.0.0'));
    }

    public function test_upgrading_a_disabled_plugin_waits_for_enable_to_migrate(): void
    {
        $this->upload('1.0.0', ['000001_create_items' => self::ITEMS]);

        $this->upload('2.0.0', [
            '000001_create_items' => self::ITEMS,
            '000002_add_color' => self::COLOR,
        ])->assertSessionHas('success');

        $this->assertFalse(Schema::hasTable('tt_greeter_items'));
        $this->plugins->enable('greeter');
        $this->assertTrue(Schema::hasColumn('tt_greeter_items', 'color'));
    }

    public function test_an_older_or_equal_version_is_refused(): void
    {
        $this->upload('1.0.0', []);

        $this->upload('1.0.0', [])->assertSessionHasErrors([
            'archive' => 'Version 1.0.0 is not newer than the installed 1.0.0.',
        ]);
        $this->upload('0.9.0', [])->assertSessionHasErrors('archive');
    }

    public function test_a_plugin_that_came_with_a_deploy_cannot_be_upgraded_by_upload(): void
    {
        $files = $this->greeterFiles('hello/', 'hello');

        $this->postZip($files)->assertSessionHasErrors('archive');
        $this->assertDirectoryDoesNotExist($this->uploads_path.'/hello');
    }

    public function test_a_failed_enable_rolls_back_and_stays_disabled(): void
    {
        $this->upload('1.0.0', [
            '000001_create_items' => self::ITEMS,
            '000002_broken' => "throw new RuntimeException('boom');",
        ]);
        $admin = Trooper::factory()->asAdministrator()->create();

        $this->actingAs($admin)
            ->post(route('admin.plugins.enable', ['slug' => 'greeter']))
            ->assertRedirect(route('admin.plugins.list'))
            ->assertSessionHas('danger');

        $this->assertFalse($this->plugins->isEnabled('greeter'));
        $this->assertFalse(Schema::hasTable('tt_greeter_items'));
        $this->assertTrue($this->hasChange(PluginChangeAction::FAILED, null, '1.0.0'));
    }

    private function installAndEnableVersionOne(): void
    {
        $this->upload('1.0.0', ['000001_create_items' => self::ITEMS])->assertSessionHas('success');
        $this->plugins->enable('greeter');
        $this->plugins->updateSettings('greeter', []);
    }

    /**
     * @param  array<string, string>  $migrations  Name suffix => up() body.
     */
    private function upload(string $version, array $migrations): TestResponse
    {
        $files = $this->greeterFiles();
        $manifest = json_decode($files['greeter/plugin.json'], true);
        $manifest['version'] = $version;
        $files['greeter/plugin.json'] = (string) json_encode($manifest);

        foreach ($migrations as $name => $up)
        {
            $files["greeter/database/migrations/2026_01_01_{$name}.php"] = $this->migration($up);
        }

        return $this->postZip($files);
    }

    private function migration(string $up): string
    {
        $down = match (true)
        {
            str_starts_with($up, "Schema::create('tt_greeter_items'") => "Schema::dropIfExists('tt_greeter_items');",
            str_contains($up, 'tt_greeter_extras') => "Schema::dropIfExists('tt_greeter_extras');",
            str_contains($up, 'color') => "Schema::table('tt_greeter_items', "
                ."fn (\$t) => \$t->dropColumn('color'));",
            default => '',
        };

        return "<?php\n\nuse Illuminate\\Support\\Facades\\Schema;\n\n"
            ."return new class extends Illuminate\\Database\\Migrations\\Migration {\n"
            ."    public function up(): void { {$up} }\n"
            ."    public function down(): void { {$down} }\n"
            ."};\n";
    }

    /**
     * @param  array<string, string>  $files
     */
    private function postZip(array $files): TestResponse
    {
        return $this->actingAs(Trooper::factory()->asAdministrator()->create())
            ->post(route('admin.plugins.install'), ['archive' => $this->pluginArchive($files)]);
    }

    private function hasChange(PluginChangeAction $action, ?string $from, string $to): bool
    {
        return PluginChange::query()
            ->where(PluginChange::SLUG, 'greeter')
            ->where(PluginChange::ACTION, $action->value)
            ->where(PluginChange::FROM_VERSION, $from)
            ->where(PluginChange::TO_VERSION, $to)
            ->exists();
    }
}
