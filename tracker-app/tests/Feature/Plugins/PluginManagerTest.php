<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use App\Events\EventPosted;
use App\Messages\Plugins\Commands\DisablePlugin;
use App\Models\Event;
use App\Models\Plugin;
use App\Plugins\PluginManager;
use App\Plugins\PluginSlots;
use HelloPlugin\HelloServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class PluginManagerTest extends TestCase
{
    use RefreshDatabase;
    use UsesFixturePlugins;

    private PluginManager $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->useFixturePlugins();
    }

    public function test_all_discovers_valid_plugins_and_skips_invalid_manifests(): void
    {
        $this->assertSame(['hello', 'missing-provider'], array_keys($this->subject->all()));
        $this->assertSame('Hello', $this->subject->find('hello')->name);
        $this->assertSame('2.1.0', $this->subject->find('hello')->version);
        $this->assertNull($this->subject->find('invalid'));
    }

    public function test_plugins_are_disabled_until_enabled(): void
    {
        $this->assertFalse($this->subject->isEnabled('hello'));
        $this->assertSame([], $this->subject->enabled());
    }

    public function test_enable_saves_state_and_runs_plugin_migrations(): void
    {
        $result = $this->subject->enable('hello');

        $this->assertTrue($result->is_enabled);
        $this->assertSame('2.1.0', $result->installed_version);
        $this->assertNotNull($result->enabled_at);
        $this->assertTrue($this->subject->isEnabled('hello'));
        $this->assertTrue(Schema::hasTable('tt_hello_greetings'));
    }

    public function test_disable_keeps_the_record_but_turns_it_off(): void
    {
        $this->subject->enable('hello');

        $this->subject->disable('hello');

        $this->assertFalse($this->subject->isEnabled('hello'));
        $this->assertDatabaseHas('tt_plugins', ['slug' => 'hello', 'is_enabled' => false]);
    }

    public function test_enable_rejects_unknown_plugins(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->subject->enable('nope');
    }

    public function test_enabled_state_is_cached_and_refreshed_on_change(): void
    {
        $this->assertFalse($this->subject->isEnabled('hello'));
        $this->assertTrue(Cache::has(PluginManager::CACHE_KEY));

        $this->subject->enable('hello');

        $this->assertTrue($this->subject->isEnabled('hello'));
    }

    public function test_load_registers_the_provider_and_its_conventional_resources(): void
    {
        $loaded = $this->subject->load($this->subject->find('hello'));
        app('router')->getRoutes()->refreshNameLookups();

        $this->assertTrue($loaded);
        $this->assertTrue($this->subject->isLoaded('hello'));
        $this->assertSame('hello plugin', config('plugins.hello.name'));
        $this->assertTrue(view()->exists('hello::greeting'));
        $this->assertTrue(app('router')->has('plugins.hello.ping'));
        $this->assertStringEndsWith('/plugins/hello/ping', route('plugins.hello.ping'));
        $this->get('/plugins/hello/ping')->assertOk()->assertSee('pong from hello plugin');
    }

    public function test_load_returns_false_when_the_provider_is_missing(): void
    {
        $loaded = $this->subject->load($this->subject->find('missing-provider'));

        $this->assertFalse($loaded);
        $this->assertFalse($this->subject->isLoaded('missing-provider'));
    }

    public function test_loaded_plugin_hooks_into_messages_events_and_slots(): void
    {
        $this->subject->load($this->subject->find('hello'));
        HelloServiceProvider::$handled = [];
        HelloServiceProvider::$posted = [];

        $this->subject->enable('hello');
        DisablePlugin::call(slug: 'hello');

        $event = Event::factory()->create();
        EventPosted::dispatch($event);

        $html = app(PluginSlots::class)->render('test.slot', ['name' => 'Rex']);

        $this->assertContains(DisablePlugin::class, HelloServiceProvider::$handled);
        $this->assertSame([$event->id], HelloServiceProvider::$posted);
        $this->assertStringContainsString('Hello, Rex!', $html);
    }

    public function test_settings_fill_in_manifest_defaults(): void
    {
        $settings = $this->subject->settings('hello');

        $this->assertSame(
            ['greeting' => 'Hello', 'api_key' => null, 'is_loud' => false],
            $settings,
        );
    }

    public function test_update_settings_casts_values_and_keeps_an_empty_secret(): void
    {
        $this->subject->updateSettings(
            'hello',
            ['greeting' => ' Howdy ', 'api_key' => 'abc', 'is_loud' => '1'],
        );
        $this->subject->updateSettings(
            'hello',
            ['greeting' => 'Hi', 'api_key' => '', 'extra' => 'ignored'],
        );

        $settings = $this->subject->settings('hello');

        $this->assertSame('Hi', $settings['greeting']);
        $this->assertSame('abc', $settings['api_key']);
        $this->assertFalse($settings['is_loud']);
        $this->assertArrayNotHasKey('extra', $this->subject->record('hello')->settings);
    }

    public function test_settings_are_encrypted_at_rest(): void
    {
        $this->subject->updateSettings('hello', ['api_key' => 'super-secret']);

        $raw = Plugin::query()->toBase()->where('slug', 'hello')->value('settings');

        $this->assertStringNotContainsString('super-secret', (string) $raw);
        $this->assertSame('super-secret', $this->subject->setting('hello', 'api_key'));
    }

    public function test_uninstall_rolls_back_migrations_and_deletes_state(): void
    {
        $this->subject->enable('hello');
        $this->subject->updateSettings('hello', ['greeting' => 'Hi']);

        $this->subject->uninstall('hello');

        $this->assertFalse($this->subject->isEnabled('hello'));
        $this->assertFalse($this->subject->isInstalled('hello'));
        $this->assertFalse(Schema::hasTable('tt_hello_greetings'));
        $this->assertDatabaseMissing('tt_plugins', ['slug' => 'hello']);
        $this->assertSame('Hello', $this->subject->setting('hello', 'greeting'));
    }

    public function test_an_uninstalled_plugin_can_be_enabled_again(): void
    {
        $this->subject->enable('hello');
        $this->subject->uninstall('hello');

        $this->subject->enable('hello');

        $this->assertTrue($this->subject->isEnabled('hello'));
        $this->assertTrue(Schema::hasTable('tt_hello_greetings'));
    }
}
