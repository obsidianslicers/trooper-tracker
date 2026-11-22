<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events\Pages;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CreateEventControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_create_event_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->get(route('admin.events.create'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/Create')
            ->where('form.status', 'draft')
            ->where('form.organization_id', null)
            ->has('host_options')
            ->has('organizations')
            ->has('event_types')
            ->has('status_options')
            ->has('plugin_fields'));
    }

    public function test_invoke_starts_with_the_host_from_the_organization_page(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $response = $this->actingAs($trooper)
            ->get(route('admin.events.create', ['organization_id' => $organization->id]));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('form.organization_id', $organization->id));
    }

    public function test_invoke_refuses_a_host_the_trooper_cannot_host_for(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $this->actingAs($trooper)
            ->get(route('admin.events.create', ['organization_id' => 999999]))
            ->assertNotFound();
    }

    public function test_invoke_forbids_a_member(): void
    {
        $trooper = Trooper::factory()->create();

        $this->actingAs($trooper)->get(route('admin.events.create'))->assertForbidden();
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.events.create'));

        $response->assertRedirect(route('auth.login'));
    }
}
