<?php

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthoritySubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_role_and_moderator_assignments(): void
    {
        // Arrange
        $org_to_add = Organization::factory()->create();
        $org_to_keep = Organization::factory()->create();
        $org_to_remove = Organization::factory()->create();

        $admin_user = Trooper::factory()->asAdministrator()->create();
        $trooper_to_update = Trooper::factory()->asModerator()
            ->withAssignment($org_to_keep, moderator: true)
            ->withAssignment($org_to_remove, moderator: true)
            ->create();

        $update_data = [
            'membership_role' => MembershipRole::MODERATOR->value,
            'moderators' => [
                $org_to_add->id => ['selected' => 1],
                $org_to_keep->id => ['selected' => 1],
                $org_to_remove->id => ['selected' => 0], // Explicitly un-selecting
            ],
        ];

        // Act
        $response = $this->actingAs($admin_user)
            ->post(route('admin.troopers.authority', $trooper_to_update), $update_data);

        // Assert
        $response->assertRedirect(route('admin.troopers.list'));

        $this->assertDatabaseHas(Trooper::class, [
            'id' => $trooper_to_update->id,
            'membership_role' => MembershipRole::MODERATOR->value,
        ]);

        // Assert new assignment was created and set to moderator
        $this->assertDatabaseHas(TrooperAssignment::class, [
            'trooper_id' => $trooper_to_update->id,
            'organization_id' => $org_to_add->id,
            'moderator' => true,
        ]);

        // Assert existing assignment was kept as moderator
        $this->assertDatabaseHas(TrooperAssignment::class, [
            'trooper_id' => $trooper_to_update->id,
            'organization_id' => $org_to_keep->id,
            'moderator' => true,
        ]);

        // Assert existing assignment was changed to not be a moderator
        $this->assertDatabaseHas(TrooperAssignment::class, [
            'trooper_id' => $trooper_to_update->id,
            'organization_id' => $org_to_remove->id,
            'moderator' => false,
        ]);
    }

    public function test_invoke_removes_moderator_assignments_when_role_is_changed_from_moderator(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;
        $organization = $region->parent;


        $admin_user = Trooper::factory()->asAdministrator()->create();
        $trooper_to_update = Trooper::factory()->asModerator()
            ->withAssignment($organization, moderator: true)
            ->create();

        $update_data = [
            'membership_role' => MembershipRole::MEMBER->value,
            'moderators' => [
                $organization->id => ['selected' => 1], // This should be ignored
            ],
        ];

        // Act
        $response = $this->actingAs($admin_user)
            ->post(route('admin.troopers.authority', $trooper_to_update), $update_data);

        // Assert
        $response->assertRedirect(route('admin.troopers.list'));

        $this->assertDatabaseHas(Trooper::class, [
            'id' => $trooper_to_update->id,
            'membership_role' => MembershipRole::MEMBER->value,
        ]);

        // Assert the trooper is no longer a moderator for the organization
        $this->assertDatabaseHas(TrooperAssignment::class, [
            'trooper_id' => $trooper_to_update->id,
            'organization_id' => $organization->id,
            'moderator' => false,
        ]);
    }
}
