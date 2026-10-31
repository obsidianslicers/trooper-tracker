<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_events_list_for_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('events.list'));

        $response->assertOk();
        $response->assertViewIs('pages.events.list');
    }

    public function test_invoke_renders_hosting_organization_parents_for_upward_filtering(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $club = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($club)->create();
        $unit = Organization::factory()->asUnit()->withParent($region)->create();

        $response = $this->actingAs($trooper)->get(route('events.list'));

        $response->assertOk();
        $response->assertSee("data-hosting-organization-parents='", false);
        $response->assertSee("\"{$region->id}\":\"{$club->id}\"", false);
        $response->assertSee("\"{$unit->id}\":\"{$region->id}\"", false);
    }

    public function test_invoke_shows_support_widget_only_when_a_goal_is_set(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        config(['tracker.support.goal' => '300']);
        $this->actingAs($trooper)->get(route('events.list'))
            ->assertSee(route('widgets.support-htmx'), false);

        config(['tracker.support.goal' => null]);
        $this->actingAs($trooper)->get(route('events.list'))
            ->assertDontSee(route('widgets.support-htmx'), false);
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('events.list'));

        $response->assertRedirect(route('auth.login'));
    }
}
