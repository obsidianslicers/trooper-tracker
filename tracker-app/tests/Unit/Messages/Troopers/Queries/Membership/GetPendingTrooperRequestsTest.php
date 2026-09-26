<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries\Membership;

use App\Messages\Troopers\Queries\Membership\GetPendingTrooperRequests;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetPendingTrooperRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_only_pending_active_requests_for_the_moderator_scope(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $allowed_org = Organization::factory()->withName('Allowed Org')->create();
        $other_org = Organization::factory()->withName('Other Org')->create();

        TrooperAssignment::factory()
            ->forTrooper($moderator)
            ->forOrganization($allowed_org)
            ->asModerator()
            ->create();

        $approved_trooper = Trooper::factory()->asMember()->withDisplayName('Approved Member')->create();
        $pending_trooper = Trooper::factory()->asPending()->withDisplayName('Pending Member')->create();
        $active_trooper = Trooper::factory()->asActive()->withDisplayName('Active Member')->create();
        $other_active_trooper = Trooper::factory()->asActive()->withDisplayName('Other Active Member')->create();

        TrooperRequest::factory()
            ->forTrooper($approved_trooper)
            ->forOrganization($allowed_org)
            ->forPrimaryOrganization($allowed_org)
            ->asApproved()
            ->create();

        TrooperRequest::factory()
            ->forTrooper($pending_trooper)
            ->forOrganization($allowed_org)
            ->forPrimaryOrganization($allowed_org)
            ->asPending()
            ->create();

        TrooperRequest::factory()
            ->forTrooper($active_trooper)
            ->forOrganization($allowed_org)
            ->forPrimaryOrganization($allowed_org)
            ->asPending()
            ->create();

        TrooperRequest::factory()
            ->forTrooper($other_active_trooper)
            ->forOrganization($other_org)
            ->forPrimaryOrganization($other_org)
            ->asPending()
            ->create();

        $subject = new GetPendingTrooperRequests($moderator);

        $result = $subject->handle();
        $request = $result->first();

        $this->assertCount(1, $result);
        $this->assertNotNull($request);
        $this->assertSame($active_trooper->id, $request->trooper_id);
        $this->assertSame($active_trooper->display_name, $request->trooper->display_name);
        $this->assertTrue($request->relationLoaded('trooper'));
        $this->assertTrue($request->relationLoaded('organization'));
        $this->assertTrue($request->relationLoaded('primary_organization'));
    }
}
