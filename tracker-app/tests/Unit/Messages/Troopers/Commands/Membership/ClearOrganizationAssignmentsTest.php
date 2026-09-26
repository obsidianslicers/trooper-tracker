<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Membership;

use App\Messages\Troopers\Commands\Membership\ClearOrganizationAssignments;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearOrganizationAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_clears_membership_for_active_and_trashed_descendant_assignments(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $primary_organization = Organization::factory()
            ->asOrganization()
            ->withNodePath('100:')
            ->create();
        $active_organization = Organization::factory()
            ->asRegion()
            ->withParent($primary_organization)
            ->withNodePath('100:200:')
            ->create();
        $trashed_organization = Organization::factory()
            ->asRegion()
            ->withParent($primary_organization)
            ->withNodePath('100:300:')
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($active_organization)
            ->asMember()
            ->create();

        $trashed_assignment = TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($trashed_organization)
            ->asMember()
            ->create();
        $trashed_assignment->delete();

        $subject = new ClearOrganizationAssignments(
            trooper_id: $trooper->{Trooper::ID},
            primary_organization: $primary_organization,
        );

        $subject->handle();

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperAssignment::ORGANIZATION_ID => $active_organization->{Organization::ID},
            TrooperAssignment::IS_MEMBER => false,
        ]);

        $updated_trashed_assignment = TrooperAssignment::withTrashed()->findOrFail(
            $trashed_assignment->{TrooperAssignment::ID},
        );

        $this->assertFalse($updated_trashed_assignment->{TrooperAssignment::IS_MEMBER});
        $this->assertTrue($updated_trashed_assignment->trashed());
    }

    public function test_handle_does_not_clear_assignments_outside_primary_organization(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $primary_organization = Organization::factory()
            ->asOrganization()
            ->withNodePath('100:')
            ->create();
        $other_primary_organization = Organization::factory()
            ->asOrganization()
            ->withNodePath('200:')
            ->create();
        $organization = Organization::factory()
            ->asRegion()
            ->withParent($other_primary_organization)
            ->withNodePath('200:300:')
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->asMember()
            ->create();

        $subject = new ClearOrganizationAssignments(
            trooper_id: $trooper->{Trooper::ID},
            primary_organization: $primary_organization,
        );

        $subject->handle();

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperAssignment::ORGANIZATION_ID => $organization->{Organization::ID},
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }
}
