<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetPendingTrooperRequestsQuery;
use App\Features\Troopers\Queries\GetPendingTrooperRequestsQueryHandler;
use App\Models\TrooperRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see GetPendingTrooperRequestsQueryHandler
 */
class GetPendingTrooperRequestsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_all_pending_requests_for_administrator(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();

        $org_a = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $org_b = Organization::factory()->asOrganization()->withNodePath('200:')->create();

        $member_a = Trooper::factory()->asMember()->create();
        $member_b = Trooper::factory()->asMember()->create();

        TrooperRequest::factory()->forTrooper($member_a)->forOrganization($org_a)->forPrimaryOrganization($org_a)->create();
        TrooperRequest::factory()->forTrooper($member_b)->forOrganization($org_b)->forPrimaryOrganization($org_b)->create();

        $handler = app(GetPendingTrooperRequestsQueryHandler::class);
        $result = $handler(new GetPendingTrooperRequestsQuery($admin));

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

        TrooperRequest::factory()->forTrooper($member_a)->forOrganization($org_a)->forPrimaryOrganization($org_a)->create();
        TrooperRequest::factory()->forTrooper($member_b)->forOrganization($org_b)->forPrimaryOrganization($org_b)->create();

        $handler = app(GetPendingTrooperRequestsQueryHandler::class);
        $result = $handler(new GetPendingTrooperRequestsQuery($moderator));

        $this->assertCount(1, $result);
        $this->assertEquals($org_a->id, $result->first()->organization_id);
    }

    public function test_invoke_excludes_approved_requests(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->asOrganization()->create();
        $member = Trooper::factory()->asMember()->create();

        TrooperRequest::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->asApproved()
            ->create();

        $handler = app(GetPendingTrooperRequestsQueryHandler::class);
        $result = $handler(new GetPendingTrooperRequestsQuery($admin));

        $this->assertCount(0, $result);
    }

    public function test_invoke_excludes_requests_from_pending_troopers(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->asOrganization()->create();
        $pending_trooper = Trooper::factory()->asPending()->create();

        TrooperRequest::factory()
            ->forTrooper($pending_trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $handler = app(GetPendingTrooperRequestsQueryHandler::class);
        $result = $handler(new GetPendingTrooperRequestsQuery($admin));

        $this->assertCount(0, $result);
    }

    public function test_invoke_eager_loads_trooper_organization_and_primary_organization(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->asOrganization()->create();
        $member = Trooper::factory()->asMember()->create();

        TrooperRequest::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $handler = app(GetPendingTrooperRequestsQueryHandler::class);
        $result = $handler(new GetPendingTrooperRequestsQuery($admin));

        $trooper_request = $result->first();
        $this->assertTrue($trooper_request->relationLoaded('trooper'));
        $this->assertTrue($trooper_request->relationLoaded('organization'));
        $this->assertTrue($trooper_request->relationLoaded('primaryOrganization'));
    }
}
