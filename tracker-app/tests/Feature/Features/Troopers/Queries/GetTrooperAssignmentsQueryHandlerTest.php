<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperAssignmentsQuery;
use App\Features\Troopers\Queries\GetTrooperAssignmentsQueryHandler;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperAssignmentsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_organizations_with_matching_assignment_property(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $org_alpha = Organization::factory()
            ->asOrganization()
            ->withName('Alpha Organization')
            ->withNodePath('100.')
            ->create();

        $region_alpha = Organization::factory()
            ->asRegion()
            ->withParent($org_alpha)
            ->withNodePath('100.200.')
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($region_alpha)
            ->asMember()
            ->create();

        $subject = new GetTrooperAssignmentsQueryHandler();

        $result = $subject(new GetTrooperAssignmentsQuery($trooper));

        $this->assertCount(1, $result);
        $this->assertSame('Alpha Organization', $result->first()->name);
        $this->assertTrue(isset($result->first()->assignment));
        $this->assertSame($region_alpha->id, $result->first()->assignment->id);
    }

    public function test_invoke_matches_most_specific_assignment_via_node_path(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $org = Organization::factory()
            ->asOrganization()
            ->withNodePath('100.')
            ->withName('Root Organization')
            ->create();

        $region = Organization::factory()
            ->asRegion()
            ->withParent($org)
            ->withNodePath('100.200.')
            ->withName('Region')
            ->create();

        $unit = Organization::factory()
            ->asUnit()
            ->withParent($region)
            ->withNodePath('100.200.300.')
            ->withName('Unit')
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($unit)
            ->asMember()
            ->create();

        $subject = new GetTrooperAssignmentsQueryHandler();

        $result = $subject(new GetTrooperAssignmentsQuery($trooper));

        $matched_org = $result->firstWhere(Organization::ID, $org->id);

        $this->assertNotNull($matched_org);
        $this->assertSame($unit->id, $matched_org->assignment->id);
    }

    public function test_invoke_returns_organizations_sorted_by_name(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        Organization::factory()->asOrganization()->withName('Zulu Organization')->create();
        Organization::factory()->asOrganization()->withName('Alpha Organization')->create();

        $subject = new GetTrooperAssignmentsQueryHandler();

        $result = $subject(new GetTrooperAssignmentsQuery($trooper));

        $this->assertSame(
            ['Alpha Organization', 'Zulu Organization'],
            $result->pluck(Organization::NAME)->all()
        );
    }
}
