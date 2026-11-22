<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Plugins\Commands;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Plugins\UsesFixturePlugins;
use Tests\TestCase;

class DisablePluginControllerTest extends TestCase
{
    use RefreshDatabase;
    use UsesFixturePlugins;

    public function test_invoke_disables_the_plugin(): void
    {
        $plugins = $this->useFixturePlugins();
        $plugins->enable('hello');
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->post(route(
            'admin.plugins.disable',
            ['slug' => 'hello'],
        ));

        $response->assertRedirect(route('admin.plugins.index'));
        $this->assertFalse($plugins->isEnabled('hello'));
    }
}
