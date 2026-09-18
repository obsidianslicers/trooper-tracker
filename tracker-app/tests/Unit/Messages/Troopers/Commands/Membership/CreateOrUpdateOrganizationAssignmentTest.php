<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Membership;

use App\Messages\Troopers\Commands\Membership\CreateOrUpdateOrganizationAssignment;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOrUpdateOrganizationAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_updates_existing_assignment(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->create([TrooperAssignment::IS_MEMBER => false]);

        $subject = new CreateOrUpdateOrganizationAssignment(
            trooper_id: $trooper->{Trooper::ID},
            organization_id: $organization->{Organization::ID},
            is_member: true,
        );

        $subject->handle();

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperAssignment::ORGANIZATION_ID => $organization->{Organization::ID},
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }

    public function test_handle_creates_missing_assignment(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $subject = new CreateOrUpdateOrganizationAssignment(
            trooper_id: $trooper->{Trooper::ID},
            organization_id: $organization->{Organization::ID},
            is_member: true,
        );

        $subject->handle();

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperAssignment::ORGANIZATION_ID => $organization->{Organization::ID},
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }

    public function test_handle_restores_trashed_assignment_and_updates_membership(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $assignment = TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->create([TrooperAssignment::IS_MEMBER => false]);
        $assignment->delete();

        $subject = new CreateOrUpdateOrganizationAssignment(
            trooper_id: $trooper->{Trooper::ID},
            organization_id: $organization->{Organization::ID},
            is_member: true,
        );

        $subject->handle();

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperAssignment::ORGANIZATION_ID => $organization->{Organization::ID},
            TrooperAssignment::IS_MEMBER => true,
            TrooperAssignment::DELETED_AT => null,
        ]);
    }
}
