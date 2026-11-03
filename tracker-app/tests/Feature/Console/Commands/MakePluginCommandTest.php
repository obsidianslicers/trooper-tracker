<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Events\EventPosted;
use App\Models\Event;
use App\Plugins\PluginAutoloader;
use App\Plugins\PluginDiscovery;
use App\Plugins\PluginManager;
use App\Plugins\PluginSlots;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MakePluginCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $plugins_path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugins_path = sys_get_temp_dir().'/tt-make-plugin-'.uniqid();
        config(['plugins.path' => $this->plugins_path]);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->plugins_path);

        parent::tearDown();
    }

    public function test_it_creates_a_starter_plugin(): void
    {
        $this->artisan('make:plugin', ['name' => 'Event Weather'])
            ->expectsOutput("Plugin created at [{$this->plugins_path}/event-weather].")
            ->assertSuccessful();

        $path = $this->plugins_path.'/event-weather';
        $manifest = json_decode(file_get_contents($path.'/plugin.json'), true);

        $this->assertSame('event-weather', $manifest['slug']);
        $this->assertSame('Event Weather', $manifest['name']);
        $this->assertSame('TroopTracker\\Plugins\\EventWeather', $manifest['namespace']);
        $this->assertFileExists($path.'/src/EventWeatherServiceProvider.php');
        $this->assertFileExists($path.'/src/Listeners/LogPostedEvent.php');
        $this->assertFileExists($path.'/routes/web.php');
        $this->assertFileExists($path.'/resources/views/event-panel.blade.php');
        $this->assertDirectoryExists($path.'/database/migrations');
        $this->assertFileDoesNotExist($path.'/composer.json');
    }

    public function test_the_generated_plugin_loads_and_hooks_in(): void
    {
        $this->artisan('make:plugin', ['name' => 'Starter Check', '--namespace' => 'Acme\\StarterCheck'])
            ->assertSuccessful();

        $plugins = new PluginManager(
            $this->app,
            new PluginDiscovery($this->plugins_path),
            $this->app->make(PluginAutoloader::class),
        );
        $this->app->instance(PluginManager::class, $plugins);

        $this->assertTrue($plugins->load($plugins->findOrFail('starter-check')));

        Log::spy();
        $event = Event::factory()->create(['name' => 'Parade']);
        EventPosted::dispatch($event);

        $html = app(PluginSlots::class)->render('events.display.sections', ['event' => $event]);

        $this->assertStringContainsString('Hello from Starter Check', $html);
        Log::shouldHaveReceived('info')->with('[starter-check] Event posted: Parade');
    }

    public function test_composer_option_writes_a_composer_package_file(): void
    {
        $this->artisan('make:plugin', ['name' => 'Shared Thing', '--composer' => true])->assertSuccessful();

        $composer = json_decode(file_get_contents($this->plugins_path.'/shared-thing/composer.json'), true);

        $this->assertSame('trooptracker-plugin', $composer['type']);
        $this->assertSame(['TroopTracker\\Plugins\\SharedThing\\' => 'src/'], $composer['autoload']['psr-4']);
    }

    public function test_it_refuses_to_overwrite_an_existing_plugin(): void
    {
        $this->artisan('make:plugin', ['name' => 'Twice'])->assertSuccessful();

        $this->artisan('make:plugin', ['name' => 'twice'])
            ->expectsOutput("A plugin already exists at [{$this->plugins_path}/twice].")
            ->assertFailed();
    }

    public function test_it_rejects_an_invalid_namespace(): void
    {
        $this->artisan('make:plugin', ['name' => 'Bad', '--namespace' => 'Not A Namespace'])
            ->expectsOutput('[Not A Namespace] is not a valid PHP namespace.')
            ->assertFailed();
    }
}
