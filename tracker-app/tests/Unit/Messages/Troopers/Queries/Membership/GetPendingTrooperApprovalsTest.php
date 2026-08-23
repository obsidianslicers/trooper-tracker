<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries\Membership;

use App\Messages\Troopers\Queries\Membership\GetPendingTrooperApprovals;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperRequest;
use Database\Factories\TrooperRequestFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetPendingTrooperApprovalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_only_pending_troopers_moderated_by_the_given_trooper(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $allowed_org = Organization::factory()->withName('Allowed Org')->create();
        $other_org = Organization::factory()->withName('Other Org')->create();

        TrooperAssignment::factory()
            ->forTrooper($moderator)
            ->forOrganization($allowed_org)
            ->asModerator()
            ->create();

        $allowed_pending = Trooper::factory()->asPending()->withDisplayName('Allowed Pending')->create();
        $other_pending = Trooper::factory()->asPending()->withDisplayName('Other Pending')->create();
        $ignored_active = Trooper::factory()->asActive()->withDisplayName('Ignored Active')->create();

        TrooperRequest::factory()
            ->forTrooper($allowed_pending)
            ->forOrganization($allowed_org)
            ->asPending()
            ->create();

        TrooperRequest::factory()
            ->forTrooper($other_pending)
            ->forOrganization($other_org)
            ->asPending()
            ->create();

        TrooperRequest::factory()
            ->forTrooper($ignored_active)
            ->forOrganization($allowed_org)
            ->asPending()
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($allowed_pending)
            ->forOrganization($allowed_org)
            ->asMember()
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($other_pending)
            ->forOrganization($other_org)
            ->asMember()
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($ignored_active)
            ->forOrganization($allowed_org)
            ->asMember()
            ->create();

        $subject = new GetPendingTrooperApprovals($moderator);

        $result = $subject->handle();

        $this->assertCount(1, $result);
        $this->assertSame(['Allowed Pending'], $result->pluck(Trooper::DISPLAY_NAME)->all());
    }
}
