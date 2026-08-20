<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries;

use App\Messages\Troopers\Queries\GetTrooperAssignments;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_all_assignments_for_trooper_with_organization_relation(): void
    {
        $trooper = Trooper::factory()->create();

        $alpha_org = Organization::factory()->withName('Alpha Club')->create();
        $beta_org = Organization::factory()->withName('Beta Region')->create();
        $other_org = Organization::factory()->withName('Other Org')->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($alpha_org)
            ->asMember()
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($beta_org)
            ->asModerator()
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($other_org)
            ->asMember()
            ->create();

        $subject = new GetTrooperAssignments($trooper);

        $result = $subject->handle();

        $this->assertCount(3, $result);
        $this->assertSame(
            [$alpha_org->{Organization::ID}, $beta_org->{Organization::ID}, $other_org->{Organization::ID}],
            $result->pluck(TrooperAssignment::ORGANIZATION_ID)->sort()->values()->all(),
        );
        $this->assertSame(
            ['Alpha Club', 'Beta Region', 'Other Org'],
            $result->pluck('organization.name')->sort()->values()->all(),
        );
    }

    public function test_handle_filters_to_member_only_assignments_when_requested(): void
    {
        $trooper = Trooper::factory()->create();

        $member_org = Organization::factory()->withName('Member Org')->create();
        $non_member_org = Organization::factory()->withName('Non Member Org')->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($member_org)
            ->asMember()
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($non_member_org)
            ->asModerator()
            ->create();

        $subject = new GetTrooperAssignments($trooper, true);

        $result = $subject->handle();

        $this->assertCount(1, $result);
        $this->assertSame($member_org->{Organization::ID}, $result->first()->{TrooperAssignment::ORGANIZATION_ID});
        $this->assertTrue($result->first()->{TrooperAssignment::IS_MEMBER});
    }
}
