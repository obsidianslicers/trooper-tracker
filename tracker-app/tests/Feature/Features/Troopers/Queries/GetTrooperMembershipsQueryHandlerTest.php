<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Enums\MembershipStatus;
use App\Features\Troopers\Queries\GetTrooperMembershipsQuery;
use App\Features\Troopers\Queries\GetTrooperMembershipsQueryHandler;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperMembershipsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_sets_identifier_and_assignment_for_matching_organizations(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $organization = Organization::factory()->asOrganization()->withNodePath('100.')->withName('Alpha')->create();
        $region = Organization::factory()->asRegion()->withParent($organization)->withNodePath('100.200.')->withName('Bravo')->create();

        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($organization)->withIdentifier('TK-12345')->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($region)->asMember()->create();

        $subject = new GetTrooperMembershipsQueryHandler;

        $result = $subject(new GetTrooperMembershipsQuery($trooper));

        $matched = $result->firstWhere('id', $organization->id);

        $this->assertNotNull($matched);
        $this->assertSame('TK-12345', $matched->identifier);
        $this->assertSame($region->id, $matched->assignment->id);
    }

    public function test_invoke_sets_membership_status_for_matching_organizations(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $organization = Organization::factory()->asOrganization()->withNodePath('100.')->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::RETIRED]);

        $subject = new GetTrooperMembershipsQueryHandler;

        $result = $subject(new GetTrooperMembershipsQuery($trooper));

        $matched = $result->firstWhere('id', $organization->id);

        $this->assertSame(MembershipStatus::RETIRED, $matched->membership_status);
    }

    public function test_invoke_sets_is_member_true_for_organizations_with_active_assignment(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $organization = Organization::factory()->asOrganization()->withNodePath('100.')->create();

        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($organization)->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $subject = new GetTrooperMembershipsQueryHandler;
        $result = $subject(new GetTrooperMembershipsQuery($trooper));

        $matched = $result->firstWhere('id', $organization->id);
        $this->assertTrue($matched->is_member);
    }

    public function test_invoke_sets_is_member_false_when_no_matching_membership(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100.')->create();

        $subject = new GetTrooperMembershipsQueryHandler;
        $result = $subject(new GetTrooperMembershipsQuery($trooper));

        $matched = $result->firstWhere('id', $organization->id);
        $this->assertFalse($matched->is_member);
    }
}
