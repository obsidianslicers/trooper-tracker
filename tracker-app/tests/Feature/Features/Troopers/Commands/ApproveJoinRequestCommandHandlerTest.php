<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\ApproveJoinRequestCommand;
use App\Features\Troopers\Commands\ApproveJoinRequestCommandHandler;
use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use App\Notifications\Troopers\JoinRequestApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @see ApproveJoinRequestCommandHandler
 */
class ApproveJoinRequestCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_sets_status_to_active(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $join_request = TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        $handler = app(ApproveJoinRequestCommandHandler::class);
        $handler(new ApproveJoinRequestCommand($join_request));

        $join_request->refresh();
        $this->assertEquals(MembershipStatus::ACTIVE, $join_request->membership_status);
    }

    public function test_invoke_creates_trooper_assignment_as_member(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $join_request = TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        $handler = app(ApproveJoinRequestCommandHandler::class);
        $handler(new ApproveJoinRequestCommand($join_request));

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }

    public function test_invoke_clears_existing_member_assignment_in_same_hierarchy(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $root = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $sibling = Organization::factory()->asRegion()->withParent($root)->withNodePath('100:200:')->create();
        $target = Organization::factory()->asRegion()->withParent($root)->withNodePath('100:300:')->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($sibling)->asMember()->create();

        $join_request = TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($target)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        $handler = app(ApproveJoinRequestCommandHandler::class);
        $handler(new ApproveJoinRequestCommand($join_request));

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $sibling->id,
            TrooperAssignment::IS_MEMBER => false,
        ]);
    }

    public function test_invoke_sends_approved_notification_to_trooper(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $join_request = TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        $handler = app(ApproveJoinRequestCommandHandler::class);
        $handler(new ApproveJoinRequestCommand($join_request));

        Notification::assertSentTo($trooper, JoinRequestApprovedNotification::class);
    }

    public function test_invoke_persists_identifier_to_primary_club_trooper_organization(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $join_request = TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->withIdentifier('TK-99999')
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        $handler = app(ApproveJoinRequestCommandHandler::class);
        $handler(new ApproveJoinRequestCommand($join_request));

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => 'TK-99999',
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);
    }
}
