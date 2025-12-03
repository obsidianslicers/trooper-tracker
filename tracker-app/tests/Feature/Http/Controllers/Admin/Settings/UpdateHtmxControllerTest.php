<?php

namespace Tests\Feature\Http\Controllers\Admin\Settings;

use App\Models\Setting;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UpdateHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_setting_clears_cache_and_returns_htmx_response(): void
    {
        // Arrange
        $admin_user = Trooper::factory()->asAdmin()->create();
        $setting = Setting::factory()->create([
            'key' => 'site_name',
            'value' => 'Old Site Name',
        ]);

        // Prime the cache to ensure it can be forgotten
        Cache::put("setting.{$setting->key}", 'Old Site Name', 60);
        $this->assertTrue(Cache::has("setting.{$setting->key}"));

        $update_data = [
            'setting' => [
                'site_name' => 'New Site Name',
            ],
        ];

        // Act
        $response = $this->actingAs($admin_user)
            ->post(route('admin.settings.update-htmx', $setting), $update_data);

        // Assert
        $response->assertOk();
        $response->assertHeader('X-Flash-Message');

        $this->assertDatabaseHas(Setting::class, [
            'key' => 'site_name',
            'value' => 'New Site Name',
        ]);

        // Assert that the specific cache key was forgotten
        $this->assertFalse(Cache::has("setting.{$setting->key}"));
    }

    public function test_invoke_as_non_admin_is_forbidden(): void
    {
        // Arrange
        $moderator_user = Trooper::factory()->asModerator()->create();
        $setting = Setting::factory()->create(['value' => 'Original Value']);
        $update_data = [
            'setting' => [
                $setting->key => 'Attempted New Value',
            ],
        ];

        // Act
        $response = $this->actingAs($moderator_user)
            ->post(route('admin.settings.update-htmx', $setting), $update_data);

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas(Setting::class, ['value' => 'Original Value']);
    }
}