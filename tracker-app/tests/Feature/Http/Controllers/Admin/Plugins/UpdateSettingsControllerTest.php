<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Plugins;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Plugins\UsesFixturePlugins;
use Tests\TestCase;

class UpdateSettingsControllerTest extends TestCase
{
    use RefreshDatabase;
    use UsesFixturePlugins;

    public function test_invoke_shows_the_settings_form_without_revealing_secrets(): void
    {
        $plugins = $this->useFixturePlugins();
        $plugins->updateSettings('hello', ['greeting' => 'Howdy', 'api_key' => 'super-secret']);
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->get(route(
            'admin.plugins.settings',
            ['slug' => 'hello'],
        ));

        $response->assertOk();
        $response->assertViewIs('pages.admin.plugins.update-settings');
        $response->assertSee('Howdy');
        $response->assertSee('Saved. Leave empty to keep it.');
        $response->assertDontSee('super-secret');
    }

    public function test_invoke_returns_not_found_for_an_unknown_plugin(): void
    {
        $this->useFixturePlugins();
        $trooper = Trooper::factory()->asAdministrator()->create();

        $this->actingAs($trooper)->get(route(
            'admin.plugins.settings',
            ['slug' => 'nope'],
        ))->assertNotFound();
    }
}
