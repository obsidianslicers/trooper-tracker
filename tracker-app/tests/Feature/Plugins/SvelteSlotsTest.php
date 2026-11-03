<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SvelteSlotsTest extends TestCase
{
    use RefreshDatabase;
    use UsesFixturePlugins;

    public function test_svelte_slots_are_shared_as_rendered_html_with_inertia_pages(): void
    {
        $plugins = $this->useFixturePlugins();
        $plugins->load($plugins->findOrFail('hello'));
        $trooper = Trooper::factory()->asActive()->create(['display_name' => 'Rex']);

        $response = $this->actingAs($trooper)->get(route('account.index'));

        $response->assertOk();
        $slots = $response->viewData('page')['props']['plugin_slots'];
        $this->assertSame(['account.profile.sections'], array_keys($slots));
        $this->assertStringContainsString('Welcome back, Rex!', $slots['account.profile.sections']);
    }

    public function test_plugin_slots_prop_is_empty_without_plugins(): void
    {
        $this->useFixturePlugins();
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('account.index'));

        $response->assertOk();
        $this->assertSame([], $response->viewData('page')['props']['plugin_slots']);
    }
}
