<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Plugins\Commands;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Plugins\UsesFixturePlugins;
use Tests\TestCase;

class UninstallPluginControllerTest extends TestCase
{
    use RefreshDatabase;
    use UsesFixturePlugins;

    public function test_invoke_uninstalls_the_plugin(): void
    {
        $plugins = $this->useFixturePlugins();
        $plugins->enable('hello');
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->post(route(
            'admin.plugins.uninstall',
            ['slug' => 'hello'],
        ));

        $response->assertRedirect(route('admin.plugins.index'));
        $response->assertSessionHas('success');
        $this->assertFalse($plugins->isInstalled('hello'));
        $this->assertFalse(Schema::hasTable('tt_hello_greetings'));
    }

    public function test_invoke_is_forbidden_for_moderators(): void
    {
        $plugins = $this->useFixturePlugins();
        $plugins->enable('hello');
        $trooper = Trooper::factory()->asModerator()->create();

        $this->actingAs($trooper)->post(route('admin.plugins.uninstall', ['slug' => 'hello']));

        $this->assertTrue($plugins->isInstalled('hello'));
    }

    public function test_list_shows_uninstall_only_for_installed_plugins(): void
    {
        $plugins = $this->useFixturePlugins();
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $this->actingAs($trooper)->get(route('admin.plugins.index'))
            ->assertInertia(fn (Assert $page) => $page->where(
                'plugins',
                fn ($rows) => collect($rows)->firstWhere('slug', 'hello')['is_installed'] === false,
            ));

        $plugins->enable('hello');

        $this->actingAs($trooper)->get(route('admin.plugins.index'))
            ->assertInertia(fn (Assert $page) => $page->where(
                'plugins',
                fn ($rows) => collect($rows)->firstWhere('slug', 'hello')['is_installed'] === true,
            ));
    }
}
