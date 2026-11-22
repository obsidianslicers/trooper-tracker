<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Plugins;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/plugins/List')
            ->where('plugins', fn ($plugins) => collect($plugins)->contains(
                fn (array $plugin) => $plugin['slug'] === 'hello'
                    && $plugin['name'] === 'Hello'
                    && $plugin['description'] === 'A test plugin.'
                    && $plugin['has_settings'] === true
            )));
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
