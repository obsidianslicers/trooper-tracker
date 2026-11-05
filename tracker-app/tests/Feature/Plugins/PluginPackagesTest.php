<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use App\Models\PluginPackage;
use App\Models\Trooper;
use App\Plugins\PluginDeployPackages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginPackagesTest extends TestCase
{
    use RefreshDatabase;
    use UsesFixturePlugins;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useFixturePlugins();
    }

    public function test_an_administrator_adds_a_package_for_the_next_deploy(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.plugins.packages.add'), [
                'name' => ' Acme/TroopTracker-Badges ',
                'version_constraint' => '^1.0',
            ])
            ->assertRedirect(route('admin.plugins.list'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tt_plugin_packages', [
            'name' => 'acme/trooptracker-badges',
            'version_constraint' => '^1.0',
        ]);
        $this->assertSame(
            ['acme/trooptracker-badges:^1.0'],
            $this->app->make(PluginDeployPackages::class)->toInstall(),
        );
        $this->artisan('plugins:packages')
            ->expectsOutput('acme/trooptracker-badges:^1.0')
            ->assertSuccessful();
    }

    public function test_the_page_lists_added_packages(): void
    {
        PluginPackage::create([PluginPackage::NAME => 'acme/badges']);

        $this->actingAs($this->admin())->get(route('admin.plugins.list'))
            ->assertSee('acme/badges')
            ->assertSee('Installs at next deploy');
    }

    public function test_bad_names_and_versions_are_refused(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.plugins.packages.add'), ['name' => 'not a package'])
            ->assertSessionHasErrors('name');
        $this->actingAs($admin)
            ->post(route('admin.plugins.packages.add'), [
                'name' => 'acme/badges',
                'version_constraint' => '^1.0 || ^2.0',
            ])
            ->assertSessionHasErrors('version_constraint');

        $this->assertDatabaseCount('tt_plugin_packages', 0);
    }

    public function test_removing_a_package_stops_the_deploy_installing_it(): void
    {
        $package = PluginPackage::create([PluginPackage::NAME => 'acme/badges']);

        $this->actingAs($this->admin())
            ->post(route('admin.plugins.packages.remove', ['package' => $package]))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('tt_plugin_packages', 0);
        $this->assertSame([], $this->app->make(PluginDeployPackages::class)->toInstall());
    }

    public function test_moderators_cannot_add_packages(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $this->actingAs($moderator)
            ->post(route('admin.plugins.packages.add'), ['name' => 'acme/badges']);

        $this->assertDatabaseCount('tt_plugin_packages', 0);
    }

    public function test_the_commands_add_and_remove_packages(): void
    {
        $this->artisan('plugins:add-package', ['name' => 'acme/badges', 'version' => '^2.0'])
            ->assertSuccessful();
        $this->assertDatabaseHas('tt_plugin_packages', ['name' => 'acme/badges']);

        $this->artisan('plugins:add-package', ['name' => 'nope'])->assertFailed();

        $this->artisan('plugins:remove-package', ['name' => 'acme/badges'])->assertSuccessful();
        $this->assertDatabaseCount('tt_plugin_packages', 0);
    }

    private function admin(): Trooper
    {
        return Trooper::factory()->asAdministrator()->create();
    }
}
