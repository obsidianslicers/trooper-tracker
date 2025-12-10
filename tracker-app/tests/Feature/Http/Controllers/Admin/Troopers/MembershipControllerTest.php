<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_memberships_for_authorized_admin(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        $organization = Organization::factory()->create([
            Organization::NAME => 'Main Org',
        ]);

        $region = Organization::factory()->create([
            Organization::PARENT_ID => $organization->id,
            Organization::TYPE => OrganizationType::REGION,
        ]);

        $unit = Organization::factory()->create([
            Organization::PARENT_ID => $region->id,
            Organization::TYPE => OrganizationType::UNIT,
        ]);

        $trooper = Trooper::factory()
            ->withOrganization($organization, 'TK-1')
            ->withAssignment($unit, member: true)
            ->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.troopers.membership', $trooper->id));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.membership');
        $response->assertViewHas('trooper', fn(Trooper $view_trooper) => $view_trooper->is($trooper));
        $response->assertViewHas('organization_memberships', function ($memberships) use ($organization, $region, $unit)
        {
            return $memberships->count() === 1
                && $memberships->first()->is($organization)
                && $memberships->first()->region?->is($region)
                && $memberships->first()->unit?->is($unit);
        });
    }

    public function test_invoke_forbidden_for_non_moderator(): void
    {
        // Arrange
        $user = Trooper::factory()->create();
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->get(route('admin.troopers.membership', $trooper->id));

        // Assert
        $response->assertForbidden();
    }
}
