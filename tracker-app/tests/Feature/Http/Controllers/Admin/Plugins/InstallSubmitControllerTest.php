<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Plugins;

use App\Models\Trooper;
use App\Plugins\PluginManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Plugins\BuildsPluginArchives;
use Tests\Feature\Plugins\UsesFixturePlugins;
use Tests\TestCase;

class InstallSubmitControllerTest extends TestCase
{
    use BuildsPluginArchives;
    use RefreshDatabase;
    use UsesFixturePlugins;

    private string $uploads_path;

    private PluginManager $plugins;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploads_path = sys_get_temp_dir().'/tt-plugin-uploads-'.uniqid();
        $this->plugins = $this->useFixturePlugins($this->uploads_path);
        config(['plugins.uploads.enabled' => true]);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->uploads_path);

        parent::tearDown();
    }

    public function test_invoke_installs_the_plugin_disabled(): void
    {
        $response = $this->postArchive($this->greeterFiles());

        $response->assertRedirect(route('admin.plugins.list'));
        $response->assertSessionHas('success');
        $this->assertFileExists($this->uploads_path.'/greeter/plugin.json');
        $this->assertFileExists($this->uploads_path.'/greeter/src/GreeterServiceProvider.php');
        $this->assertNotNull($this->plugins->find('greeter'));
        $this->assertFalse($this->plugins->isEnabled('greeter'));
    }

    public function test_invoke_accepts_plugin_json_at_the_archive_root(): void
    {
        $this->postArchive($this->greeterFiles(''))->assertSessionHas('success');

        $this->assertFileExists($this->uploads_path.'/greeter/plugin.json');
    }

    public function test_invoke_ignores_macos_metadata(): void
    {
        $files = $this->greeterFiles() + ['__MACOSX/greeter/._plugin.json' => 'x'];

        $this->postArchive($files)->assertSessionHas('success');

        $this->assertDirectoryDoesNotExist($this->uploads_path.'/greeter/__MACOSX');
    }

    public function test_invoke_refuses_path_traversal(): void
    {
        $files = $this->greeterFiles() + ['greeter/../../escaped.php' => '<?php'];

        $this->postArchive($files)->assertSessionHasErrors('archive');

        $this->assertFileDoesNotExist(dirname($this->uploads_path).'/escaped.php');
        $this->assertDirectoryDoesNotExist($this->uploads_path.'/greeter');
    }

    public function test_invoke_refuses_an_archive_without_plugin_json(): void
    {
        $this->postArchive(['greeter/readme.txt' => 'hi'])->assertSessionHasErrors('archive');
    }

    public function test_invoke_refuses_files_outside_the_plugin_folder(): void
    {
        $files = $this->greeterFiles() + ['other/file.txt' => 'hi'];

        $this->postArchive($files)->assertSessionHasErrors('archive');
    }

    public function test_invoke_refuses_an_invalid_manifest(): void
    {
        $files = ['greeter/plugin.json' => '{"slug": "Not Valid", "provider": "X"}'];

        $this->postArchive($files)->assertSessionHasErrors('archive');
    }

    public function test_invoke_refuses_a_slug_that_is_already_installed(): void
    {
        $files = $this->greeterFiles('hello/', 'hello');

        $this->postArchive($files)->assertSessionHasErrors('archive');

        $this->assertDirectoryDoesNotExist($this->uploads_path.'/hello');
    }

    public function test_invoke_refuses_too_large_once_unzipped(): void
    {
        config(['plugins.uploads.max_unzipped_kilobytes' => 1]);
        $files = $this->greeterFiles() + ['greeter/big.txt' => str_repeat('a', 4096)];

        $this->postArchive($files)->assertSessionHasErrors('archive');
    }

    public function test_invoke_installs_a_plugin_that_needs_new_packages_for_the_deploy(): void
    {
        $files = $this->greeterFiles() + ['greeter/composer.json' => (string) json_encode([
            'require' => ['php' => '^8.2', 'acme/not-installed' => '^1.0'],
        ])];

        $this->postArchive($files)->assertSessionHas('success');

        $this->assertFileExists($this->uploads_path.'/greeter/plugin.json');
    }

    public function test_invoke_refuses_a_plugin_this_server_cannot_run(): void
    {
        $files = $this->greeterFiles() + ['greeter/composer.json' => (string) json_encode([
            'require' => ['php' => '>=99.0', 'acme/not-installed' => '^1.0'],
        ])];

        $this->postArchive($files)->assertSessionHasErrors('archive');

        $this->assertDirectoryDoesNotExist($this->uploads_path.'/greeter');
    }

    public function test_invoke_refuses_a_bad_package_name_or_version(): void
    {
        foreach (['../../etc' => '*', 'acme/badges' => 'dev-main#abc123'] as $name => $constraint)
        {
            $files = $this->greeterFiles() + ['greeter/composer.json' => (string) json_encode([
                'require' => [$name => $constraint],
            ])];

            $this->postArchive($files)->assertSessionHasErrors('archive');
        }

        $this->assertDirectoryDoesNotExist($this->uploads_path.'/greeter');
    }

    public function test_invoke_accepts_a_plugin_that_only_needs_packages_the_app_has(): void
    {
        $files = $this->greeterFiles() + ['greeter/composer.json' => (string) json_encode([
            'require' => ['php' => '>=8.2', 'laravel/framework' => '>=11.0', 'ext-json' => '*'],
        ])];

        $this->postArchive($files)->assertSessionHas('success');
    }

    public function test_invoke_refuses_a_file_that_is_not_a_zip(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $file = \Illuminate\Http\UploadedFile::fake()->create('plugin.txt', 1, 'text/plain');

        $this->actingAs($trooper)
            ->post(route('admin.plugins.install'), ['archive' => $file])
            ->assertSessionHasErrors('archive');
    }

    public function test_invoke_is_forbidden_when_uploads_are_off(): void
    {
        config(['plugins.uploads.enabled' => false]);

        $this->postArchive($this->greeterFiles())->assertForbidden();

        $this->assertDirectoryDoesNotExist($this->uploads_path.'/greeter');
    }

    public function test_invoke_is_forbidden_for_moderators(): void
    {
        $trooper = Trooper::factory()->asModerator()->create();

        $this->actingAs($trooper)->post(route('admin.plugins.install'), [
            'archive' => $this->pluginArchive($this->greeterFiles()),
        ]);

        $this->assertDirectoryDoesNotExist($this->uploads_path.'/greeter');
    }

    public function test_uninstall_deletes_an_uploaded_plugin(): void
    {
        $this->postArchive($this->greeterFiles());
        $trooper = Trooper::factory()->asAdministrator()->create();

        $this->actingAs($trooper)
            ->post(route('admin.plugins.uninstall', ['slug' => 'greeter']))
            ->assertRedirect(route('admin.plugins.list'));

        $this->assertDirectoryDoesNotExist($this->uploads_path.'/greeter');
        $this->assertNull($this->plugins->find('greeter'));
    }

    public function test_list_shows_the_upload_form_only_when_uploads_are_on(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $this->actingAs($trooper)->get(route('admin.plugins.list'))
            ->assertSee(route('admin.plugins.install'));

        config(['plugins.uploads.enabled' => false]);

        $this->actingAs($trooper)->get(route('admin.plugins.list'))
            ->assertDontSee(route('admin.plugins.install'))
            ->assertSee('TRACKER_PLUGINS_ALLOW_UPLOADS');
    }

    /**
     * @param  array<string, string>  $files
     */
    private function postArchive(array $files): \Illuminate\Testing\TestResponse
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        return $this->actingAs($trooper)->post(route('admin.plugins.install'), [
            'archive' => $this->pluginArchive($files),
        ]);
    }
}
