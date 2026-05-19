<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Observers;

use App\Models\Observers\TrooperAssignmentObserver;
use App\Models\Organization;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperAssignmentObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_allows_member_assignment_on_non_leaf_organization(): void
    {
        $root = Organization::factory()->asOrganization()->create();
        Organization::factory()->asRegion()->withParent($root)->create();

        $subject = new TrooperAssignmentObserver();
        $assignment = TrooperAssignment::factory()
            ->forOrganization($root)
            ->asMember()
            ->make();

        $subject->saving($assignment);

        $this->assertTrue(true);
    }

    public function test_saving_allows_member_assignment_on_leaf_organization(): void
    {
        $leaf = Organization::factory()->asUnit()->create();

        $subject = new TrooperAssignmentObserver();
        $assignment = TrooperAssignment::factory()
            ->forOrganization($leaf)
            ->asMember()
            ->make();

        $subject->saving($assignment);

        $this->assertTrue(true);
    }
}