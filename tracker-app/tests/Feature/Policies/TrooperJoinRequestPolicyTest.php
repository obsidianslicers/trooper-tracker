<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\JoinRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Policies\TrooperJoinRequestPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see TrooperJoinRequestPolicy
 */
class TrooperJoinRequestPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderate_allows_administrator_for_any_join_request(): void
    {
        $policy = new TrooperJoinRequestPolicy;
        $admin = Trooper::factory()->asAdministrator()->create();

        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $member = Trooper::factory()->asMember()->create();

        $join_request = JoinRequest::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $this->assertTrue($policy->moderate($admin, $join_request));
    }

    public function test_moderate_allows_moderator_within_their_org_tree(): void
    {
        $policy = new TrooperJoinRequestPolicy;
        $moderator = Trooper::factory()->asModerator()->create();

        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($organization)->asModerator()->create();

        $member = Trooper::factory()->asMember()->create();
        $join_request = JoinRequest::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $this->assertTrue($policy->moderate($moderator, $join_request));
    }

    public function test_moderate_denies_moderator_outside_their_org_tree(): void
    {
        $policy = new TrooperJoinRequestPolicy;
        $moderator = Trooper::factory()->asModerator()->create();

        $org_a = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $org_b = Organization::factory()->asOrganization()->withNodePath('200:')->create();

        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org_a)->asModerator()->create();

        $member = Trooper::factory()->asMember()->create();
        $join_request = JoinRequest::factory()
            ->forTrooper($member)
            ->forOrganization($org_b)
            ->forPrimaryOrganization($org_b)
            ->create();

        $this->assertFalse($policy->moderate($moderator, $join_request));
    }
}
