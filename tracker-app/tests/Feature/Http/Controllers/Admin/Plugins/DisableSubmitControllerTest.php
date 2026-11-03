<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Plugins;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Plugins\UsesFixturePlugins;
use Tests\TestCase;

class DisableSubmitControllerTest extends TestCase
{
    use RefreshDatabase;
    use UsesFixturePlugins;

    public function test_invoke_disables_the_plugin(): void
    {
        $plugins = $this->useFixturePlugins();
        $plugins->enable('hello');
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->post(route('admin.plugins.disable', ['slug' => 'hello']));

        $response->assertRedirect(route('admin.plugins.list'));
        $this->assertFalse($plugins->isEnabled('hello'));
    }
}
