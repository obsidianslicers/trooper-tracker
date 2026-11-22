<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Organizations;

use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CostumesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_organization_costumes_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $organization = Organization::factory()->create();
        $costume = Costume::factory()->create();

        OrganizationCostume::factory()->forOrganization($organization)->forCostume($costume)->create();

        $response = $this->actingAs($trooper)->get(route('admin.organizations.costumes', ['organization' => $organization->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/organizations/Costumes')
            ->where('costumes.0.name', $costume->name));
    }

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->get(route('admin.organizations.costumes', ['organization' => $organization->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
