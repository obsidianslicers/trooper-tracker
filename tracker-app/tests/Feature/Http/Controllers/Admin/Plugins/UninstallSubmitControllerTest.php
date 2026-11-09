<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Plugins;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Plugins\UsesFixturePlugins;
use Tests\TestCase;

class UninstallSubmitControllerTest extends TestCase
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

        $response->assertRedirect(route('admin.plugins.list'));
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

        $this->actingAs($trooper)->get(route('admin.plugins.list'))
            ->assertDontSee(route('admin.plugins.uninstall', ['slug' => 'hello']));

        $plugins->enable('hello');

        $this->actingAs($trooper)->get(route('admin.plugins.list'))
            ->assertSee(route('admin.plugins.uninstall', ['slug' => 'hello']));
    }
}
