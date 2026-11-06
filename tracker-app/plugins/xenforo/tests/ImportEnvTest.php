<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use App\Plugins\PluginManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Messages\ImportXenforoEnv;

class ImportEnvTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    public function test_enabling_copies_env_values_to_the_settings(): void
    {
        $this->fakeEnv();

        $plugins = $this->enableXenforo();

        $settings = $plugins->settings('xenforo');
        $this->assertSame('https://env.example.com', $settings['base_url']);
        $this->assertSame('env-key', $settings['api_key']);
        $this->assertSame('env-secret', $settings['client_secret']);
        $this->assertTrue($settings['require_xenforo']);
        $this->assertNull($settings['webhook_secret']);
    }

    public function test_import_keeps_saved_settings(): void
    {
        $plugins = $this->enableXenforo();
        $plugins->updateSettings('xenforo', ['base_url' => 'https://saved.example.com']);
        $this->fakeEnv();

        $subject = ImportXenforoEnv::call();

        $this->assertNotContains('base_url', $subject);
        $this->assertContains('api_key', $subject);
        $this->assertSame('https://saved.example.com', $plugins->setting('xenforo', 'base_url'));
    }

    public function test_command_overwrites_saved_settings_with_force(): void
    {
        $plugins = $this->enableXenforo();
        $plugins->updateSettings('xenforo', ['base_url' => 'https://saved.example.com']);
        config(['plugins.xenforo.base_url' => 'https://env.example.com']);

        $this->artisan('xenforo:import-env', ['--force' => true])
            ->expectsOutputToContain('base_url')
            ->assertSuccessful();

        $this->assertSame('https://env.example.com', $plugins->setting('xenforo', 'base_url'));
    }

    public function test_command_says_when_there_is_nothing_to_copy(): void
    {
        $this->enableXenforo();
        config(['plugins.xenforo.base_url' => null, 'plugins.xenforo.api_key' => null]);

        $this->artisan('xenforo:import-env')
            ->expectsOutputToContain('Nothing to copy')
            ->assertSuccessful();
    }

    public function test_import_leaves_other_settings_alone(): void
    {
        $plugins = $this->app->make(PluginManager::class);
        $this->enableXenforo();
        $plugins->updateSettings('xenforo', ['name' => 'Saved Forum']);
        config(['plugins.xenforo.api_user' => '5']);

        ImportXenforoEnv::call();

        $this->assertSame('Saved Forum', $plugins->setting('xenforo', 'name'));
        $this->assertSame('5', $plugins->setting('xenforo', 'api_user'));
    }

    private function fakeEnv(): void
    {
        config([
            'plugins.xenforo.base_url' => 'https://env.example.com',
            'plugins.xenforo.api_key' => 'env-key',
            'plugins.xenforo.client_secret' => 'env-secret',
            'plugins.xenforo.webhook_secret' => '',
            'plugins.xenforo.require_xenforo' => true,
        ]);
    }
}
