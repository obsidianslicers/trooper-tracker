<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\SubmitJoinRequestCommand;
use App\Features\Troopers\Commands\SubmitJoinRequestCommandHandler;
use App\Enums\MembershipStatus;
use App\Jobs\SendJoinRequestNotificationsJob;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * @see SubmitJoinRequestCommandHandler
 */
class SubmitJoinRequestCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_pending_trooper_organization(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        Queue::fake();

        $handler = app(SubmitJoinRequestCommandHandler::class);
        $handler(new SubmitJoinRequestCommand($trooper, $organization, 'TK-12345'));

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING,
        ]);
    }

    public function test_invoke_cancels_other_pending_requests_in_same_root_family(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $root = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $sibling = Organization::factory()->asRegion()->withParent($root)->withNodePath('100:200:')->create();
        $target = Organization::factory()->asRegion()->withParent($root)->withNodePath('100:300:')->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($sibling)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        Queue::fake();

        $handler = app(SubmitJoinRequestCommandHandler::class);
        $handler(new SubmitJoinRequestCommand($trooper, $target, 'TK-12345'));

        $this->assertSoftDeleted('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $sibling->id,
        ]);
    }

    public function test_invoke_does_not_cancel_pending_requests_in_different_families(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $org_a = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $org_b = Organization::factory()->asOrganization()->withNodePath('200:')->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($org_b)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        Queue::fake();

        $handler = app(SubmitJoinRequestCommandHandler::class);
        $handler(new SubmitJoinRequestCommand($trooper, $org_a, 'TK-12345'));

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $org_b->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING,
        ]);
    }

    public function test_invoke_dispatches_send_notifications_job(): void
    {
        Queue::fake();

        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $handler = app(SubmitJoinRequestCommandHandler::class);
        $handler(new SubmitJoinRequestCommand($trooper, $organization, 'TK-12345'));

        Queue::assertPushed(SendJoinRequestNotificationsJob::class);
    }
}
