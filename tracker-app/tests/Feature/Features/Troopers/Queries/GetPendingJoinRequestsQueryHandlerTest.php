<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetPendingJoinRequestsQuery;
use App\Features\Troopers\Queries\GetPendingJoinRequestsQueryHandler;
use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see GetPendingJoinRequestsQueryHandler
 */
class GetPendingJoinRequestsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_all_pending_requests_for_administrator(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();

        $org_a = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $org_b = Organization::factory()->asOrganization()->withNodePath('200:')->create();

        $member_a = Trooper::factory()->asMember()->create();
        $member_b = Trooper::factory()->asMember()->create();

        TrooperOrganization::factory()
            ->forTrooper($member_a)
            ->forOrganization($org_a)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        TrooperOrganization::factory()
            ->forTrooper($member_b)
            ->forOrganization($org_b)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        $handler = app(GetPendingJoinRequestsQueryHandler::class);
        $result = $handler(new GetPendingJoinRequestsQuery($admin));

        $this->assertCount(2, $result);
    }

    public function test_invoke_returns_only_requests_in_moderators_tree(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $org_a = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $org_b = Organization::factory()->asOrganization()->withNodePath('200:')->create();

        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org_a)->asModerator()->create();

        $member_a = Trooper::factory()->asMember()->create();
        $member_b = Trooper::factory()->asMember()->create();

        TrooperOrganization::factory()
            ->forTrooper($member_a)
            ->forOrganization($org_a)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        TrooperOrganization::factory()
            ->forTrooper($member_b)
            ->forOrganization($org_b)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        $handler = app(GetPendingJoinRequestsQueryHandler::class);
        $result = $handler(new GetPendingJoinRequestsQuery($moderator));

        $this->assertCount(1, $result);
        $this->assertEquals($org_a->id, $result->first()->organization_id);
    }

    public function test_invoke_excludes_non_pending_requests(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->asOrganization()->create();
        $member = Trooper::factory()->asMember()->create();

        TrooperOrganization::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE]);

        $handler = app(GetPendingJoinRequestsQueryHandler::class);
        $result = $handler(new GetPendingJoinRequestsQuery($admin));

        $this->assertCount(0, $result);
    }

    public function test_invoke_eager_loads_trooper_and_organization(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->asOrganization()->create();
        $member = Trooper::factory()->asMember()->create();

        TrooperOrganization::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        $handler = app(GetPendingJoinRequestsQueryHandler::class);
        $result = $handler(new GetPendingJoinRequestsQuery($admin));

        $join_request = $result->first();
        $this->assertTrue($join_request->relationLoaded('trooper'));
        $this->assertTrue($join_request->relationLoaded('organization'));
    }
}
