<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Plugins;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Plugins\UsesFixturePlugins;
use Tests\TestCase;

class UpdateSettingsSubmitControllerTest extends TestCase
{
    use RefreshDatabase;
    use UsesFixturePlugins;

    public function test_invoke_saves_the_settings(): void
    {
        $plugins = $this->useFixturePlugins();
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->post(route(
            'admin.plugins.settings',
            ['slug' => 'hello'],
        ), [
            'settings' => ['greeting' => 'Howdy', 'api_key' => 'abc', 'is_loud' => '1'],
        ]);

        $response->assertRedirect(route('admin.plugins.list'));
        $this->assertSame(
            ['greeting' => 'Howdy', 'api_key' => 'abc', 'is_loud' => true],
            $plugins->settings('hello'),
        );
    }

    public function test_invoke_validates_values_against_the_setting_type(): void
    {
        $plugins = $this->useFixturePlugins();
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->post(route(
            'admin.plugins.settings',
            ['slug' => 'hello'],
        ), [
            'settings' => ['is_loud' => 'not-a-boolean'],
        ]);

        $response->assertSessionHasErrors('settings.is_loud');
        $this->assertNull($plugins->record('hello'));
    }
}
