<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Plugins\Pages;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Plugins\UsesFixturePlugins;
use Tests\TestCase;

class EditPluginSettingsControllerTest extends TestCase
{
    use RefreshDatabase;
    use UsesFixturePlugins;

    public function test_invoke_shows_the_settings_form_without_revealing_secrets(): void
    {
        $plugins = $this->useFixturePlugins();
        $plugins->updateSettings('hello', ['greeting' => 'Howdy', 'api_key' => 'super-secret']);
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->get(route(
            'admin.plugins.settings.edit',
            ['slug' => 'hello'],
        ));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/plugins/UpdateSettings')
            ->where('settings', fn ($settings) => collect($settings)->firstWhere('key', 'greeting')['value'] === 'Howdy'
                && collect($settings)->firstWhere('key', 'api_key')['is_saved'] === true
                && collect($settings)->firstWhere('key', 'api_key')['value'] === ''));
        $response->assertDontSee('super-secret');
    }

    public function test_invoke_returns_not_found_for_an_unknown_plugin(): void
    {
        $this->useFixturePlugins();
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $this->actingAs($trooper)->get(route(
            'admin.plugins.settings.edit',
            ['slug' => 'nope'],
        ))->assertNotFound();
    }
}
