<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Plugins;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Plugins\UsesFixturePlugins;
use Tests\TestCase;

class EnableSubmitControllerTest extends TestCase
{
    use RefreshDatabase;
    use UsesFixturePlugins;

    public function test_invoke_enables_the_plugin(): void
    {
        $plugins = $this->useFixturePlugins();
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->post(route(
            'admin.plugins.enable',
            ['slug' => 'hello'],
        ));

        $response->assertRedirect(route('admin.plugins.list'));
        $response->assertSessionHas('success');
        $this->assertTrue($plugins->isEnabled('hello'));
        $this->assertDatabaseHas('tt_plugins', ['slug' => 'hello', 'created_id' => $trooper->id]);
    }

    public function test_invoke_returns_not_found_for_an_unknown_plugin(): void
    {
        $this->useFixturePlugins();
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->post(route(
            'admin.plugins.enable',
            ['slug' => 'nope'],
        ));

        $response->assertNotFound();
    }

    public function test_invoke_is_forbidden_for_moderators(): void
    {
        $plugins = $this->useFixturePlugins();
        $trooper = Trooper::factory()->asModerator()->create();

        $this->actingAs($trooper)->post(route('admin.plugins.enable', ['slug' => 'hello']));

        $this->assertFalse($plugins->isEnabled('hello'));
    }
}
