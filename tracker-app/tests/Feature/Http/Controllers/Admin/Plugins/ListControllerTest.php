<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Plugins;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Plugins\UsesFixturePlugins;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;
    use UsesFixturePlugins;

    public function test_invoke_lists_discovered_plugins_for_administrators(): void
    {
        $this->useFixturePlugins();
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->get(route('admin.plugins.list'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.plugins.list');
        $response->assertSee('Hello');
        $response->assertSee('A test plugin.');
        $response->assertSee(route('admin.plugins.settings', ['slug' => 'hello']));
    }

    public function test_invoke_is_forbidden_for_moderators(): void
    {
        $trooper = Trooper::factory()->asModerator()->create();

        $response = $this->actingAs($trooper)->get(route('admin.plugins.list'));

        $this->assertContains($response->status(), [302, 403]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $this->get(route('admin.plugins.list'))->assertRedirect(route('auth.login'));
    }
}
