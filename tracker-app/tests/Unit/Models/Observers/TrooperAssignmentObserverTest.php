<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Observers;

use App\Models\Observers\TrooperAssignmentObserver;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
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
}