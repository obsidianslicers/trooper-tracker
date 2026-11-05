<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Plugins\UsesFixturePlugins;
use Tests\TestCase;

class PluginCommandsTest extends TestCase
{
    use RefreshDatabase;
    use UsesFixturePlugins;

    public function test_list_shows_discovered_plugins(): void
    {
        $this->useFixturePlugins();

        $this->artisan('plugins:list')
            ->expectsTable(['Slug', 'Name', 'Version', 'Source', 'Enabled'], [
                ['hello', 'Hello', '2.1.0', 'local', 'no'],
                ['missing-provider', 'Missing Provider', '0.0.0', 'local', 'no'],
            ])
            ->assertSuccessful();
    }

    public function test_enable_and_disable_change_the_plugin_state(): void
    {
        $plugins = $this->useFixturePlugins();

        $this->artisan('plugins:enable', ['slug' => 'hello'])
            ->expectsOutput('Plugin [hello] enabled.')
            ->assertSuccessful();
        $this->assertTrue($plugins->isEnabled('hello'));

        $this->artisan('plugins:disable', ['slug' => 'hello'])
            ->expectsOutput('Plugin [hello] disabled.')
            ->assertSuccessful();
        $this->assertFalse($plugins->isEnabled('hello'));
    }

    public function test_enable_fails_for_an_unknown_plugin(): void
    {
        $this->useFixturePlugins();

        $this->artisan('plugins:enable', ['slug' => 'nope'])
            ->expectsOutput('No plugin found with slug [nope].')
            ->assertFailed();
    }

    public function test_uninstall_asks_before_deleting_data(): void
    {
        $plugins = $this->useFixturePlugins();
        $plugins->enable('hello');

        $this->artisan('plugins:uninstall', ['slug' => 'hello'])
            ->expectsConfirmation(
                'Uninstall [hello]? This deletes its tables and settings and cannot be undone.',
                'no',
            )
            ->expectsOutput('Cancelled.')
            ->assertSuccessful();
        $this->assertTrue($plugins->isInstalled('hello'));

        $this->artisan('plugins:uninstall', ['slug' => 'hello', '--force' => true])
            ->expectsOutput('Plugin [hello] uninstalled. Remove its files with your next deploy.')
            ->assertSuccessful();
        $this->assertFalse($plugins->isInstalled('hello'));
    }
}
