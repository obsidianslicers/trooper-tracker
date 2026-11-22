<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Organizations;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_update_organization_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $organization = Organization::factory()->create();

        $response = $this->actingAs($trooper)->get(route('admin.organizations.update', ['organization' => $organization->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/organizations/Update')
            ->where('organization.name', $organization->name));
    }

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->get(route('admin.organizations.update', ['organization' => $organization->id]));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_shows_the_roster_sync_choice_for_a_club(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $organization = Organization::factory()->asOrganization()->create([
            Organization::SYNCHRONIZER => 'from-a-disabled-plugin',
        ]);

        $this->actingAs($trooper)
            ->get(route('admin.organizations.update', ['organization' => $organization->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('organization.synchronizer', 'from-a-disabled-plugin')
                ->where('synchronizer_options', fn ($options) => collect($options)->pluck('label')
                    ->contains('Rebel Legion')
                    && collect($options)->pluck('label')
                        ->contains('from-a-disabled-plugin (not available)')));
    }

    public function test_invoke_hides_the_roster_sync_choice_for_a_region(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $organization = Organization::factory()->asRegion()->create();

        $this->actingAs($trooper)
            ->get(route('admin.organizations.update', ['organization' => $organization->id]))
            ->assertInertia(fn (Assert $page) => $page->where('synchronizer_options', null));
    }
}
