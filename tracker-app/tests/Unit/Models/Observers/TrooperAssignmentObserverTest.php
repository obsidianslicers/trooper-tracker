<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Observers;

use App\Enums\MembershipRole;
use App\Models\Costume;
use App\Models\Observers\TrooperAssignmentObserver;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperAssignmentObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_allows_member_assignment_when_existing_member_is_in_different_tree(): void
    {
        $trooper = Trooper::factory()->create();

        $tree_a_root = Organization::factory()->asOrganization()->create();
        $tree_b_root = Organization::factory()->asOrganization()->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($tree_b_root)
            ->asMember()
            ->create();

        $subject = new TrooperAssignmentObserver();

        $assignment = TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($tree_a_root)
            ->asMember()
            ->make();

        $subject->saving($assignment);

        $this->assertTrue(true);
    }

    public function test_saving_throws_when_existing_member_is_ancestor_of_selected_organization(): void
    {
        $trooper = Trooper::factory()->create();

        $root = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($root)->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($root)
            ->asMember()
            ->create();

        $subject = new TrooperAssignmentObserver();

        $assignment = TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($region)
            ->asMember()
            ->make();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Trooper can only have one primary membership in the same organization hierarchy.'
        );

        $subject->saving($assignment);

        $this->assertTrue(true);
    }

    public function test_saving_throws_when_existing_member_is_descendant_of_selected_organization(): void
    {
        $trooper = Trooper::factory()->create();

        $root = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($root)->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($region)
            ->asMember()
            ->create();

        $subject = new TrooperAssignmentObserver();

        $assignment = TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($root)
            ->asMember()
            ->make();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Trooper can only have one primary membership in the same organization hierarchy.'
        );

        $subject->saving($assignment);

        $this->assertTrue(true);
    }

    public function test_saving_skips_hierarchy_conflict_check_when_assignment_is_not_member(): void
    {
        $trooper = Trooper::factory()->create();

        $root = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($root)->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($root)
            ->asMember()
            ->create();

        $subject = new TrooperAssignmentObserver();

        $assignment = TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($region)
            ->make([
                TrooperAssignment::IS_MEMBER => false,
            ]);

        $subject->saving($assignment);

        $this->assertTrue(true);
    }

    public function test_saving_throws_for_visitor_assigned_to_sub_organization(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::VISITOR,
        ]);

        $root = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($root)->create();

        $subject = new TrooperAssignmentObserver();

        $assignment = TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($region)
            ->make();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Visitors can only join top-level organizations.');

        $subject->saving($assignment);
    }

    public function test_saving_passes_for_visitor_assigned_to_top_level_org(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::VISITOR,
        ]);

        $root = Organization::factory()->asOrganization()->create();

        $subject = new TrooperAssignmentObserver();

        $assignment = TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($root)
            ->make();

        $subject->saving($assignment);

        $this->assertTrue(true);
    }

    public function test_saving_allows_updating_same_member_assignment(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $existing = TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->asMember()
            ->create();

        $existing->{TrooperAssignment::SHOULD_NOTIFY} = true;

        $subject = new TrooperAssignmentObserver();

        $subject->saving($existing);

        $this->assertTrue(true);
    }

    public function test_created_member_assignment_creates_handler_and_command_staff_trooper_costumes(): void
    {
        Costume::factory()->withName(Costume::HANDLER)->create();
        Costume::factory()->withName(Costume::COMMAND_STAFF)->create();

        $org = Organization::factory()->asOrganization()->create();
        // OrganizationObserver fires on org save → creates OrganizationCostume for Handler + Command Staff

        $trooper = Trooper::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org)
            ->asMember()
            ->create();
        // TrooperAssignmentObserver.created() fires → syncSpecialCostumes() → creates TrooperCostume records

        $handler_org_costume = OrganizationCostume::whereHas('costume', fn ($q) => $q->where(Costume::NAME, Costume::HANDLER))
            ->where(OrganizationCostume::ORGANIZATION_ID, $org->id)
            ->first();

        $command_staff_org_costume = OrganizationCostume::whereHas('costume', fn ($q) => $q->where(Costume::NAME, Costume::COMMAND_STAFF))
            ->where(OrganizationCostume::ORGANIZATION_ID, $org->id)
            ->first();

        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $handler_org_costume->id,
        ]);
        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $command_staff_org_costume->id,
        ]);
    }

    public function test_removing_member_flag_soft_deletes_handler_and_command_staff_trooper_costumes(): void
    {
        Costume::factory()->withName(Costume::HANDLER)->create();
        Costume::factory()->withName(Costume::COMMAND_STAFF)->create();

        $org = Organization::factory()->asOrganization()->create();
        $trooper = Trooper::factory()->create();

        $assignment = TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org)
            ->asMember()
            ->create();

        $assignment->{TrooperAssignment::IS_MEMBER} = false;
        $assignment->save();

        $this->assertSame(
            0,
            TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)->count()
        );
        $this->assertSame(
            2,
            TrooperCostume::withTrashed()->where(TrooperCostume::TROOPER_ID, $trooper->id)->count()
        );
    }

    public function test_restoring_member_flag_restores_soft_deleted_handler_and_command_staff_trooper_costumes(): void
    {
        Costume::factory()->withName(Costume::HANDLER)->create();
        Costume::factory()->withName(Costume::COMMAND_STAFF)->create();

        $org = Organization::factory()->asOrganization()->create();
        $trooper = Trooper::factory()->create();

        $assignment = TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org)
            ->asMember()
            ->create();

        $assignment->{TrooperAssignment::IS_MEMBER} = false;
        $assignment->save();

        $assignment->{TrooperAssignment::IS_MEMBER} = true;
        $assignment->save();

        $this->assertSame(
            2,
            TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)->count()
        );
    }
}