<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Enums\JoinRequestStatus;
use App\Features\Troopers\Commands\SubmitJoinRequestCommand;
use App\Features\Troopers\Commands\SubmitJoinRequestCommandHandler;
use App\Jobs\SendJoinRequestNotificationsJob;
use App\Models\JoinRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * @see SubmitJoinRequestCommandHandler
 */
class SubmitJoinRequestCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_pending_join_request(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        Queue::fake();

        $handler = app(SubmitJoinRequestCommandHandler::class);
        $handler(new SubmitJoinRequestCommand($trooper, $organization, 'TK-12345'));

        $this->assertDatabaseHas('tt_join_requests', [
            JoinRequest::TROOPER_ID              => $trooper->id,
            JoinRequest::ORGANIZATION_ID         => $organization->id,
            JoinRequest::PRIMARY_ORGANIZATION_ID => $organization->id,
            JoinRequest::IDENTIFIER              => 'TK-12345',
            JoinRequest::STATUS                  => JoinRequestStatus::PENDING,
        ]);
    }

    public function test_invoke_does_not_create_trooper_organization(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        Queue::fake();

        $handler = app(SubmitJoinRequestCommandHandler::class);
        $handler(new SubmitJoinRequestCommand($trooper, $organization, null));

        $this->assertDatabaseEmpty('tt_trooper_organizations');
        $this->assertDatabaseEmpty('tt_trooper_assignments');
    }

    public function test_invoke_cancels_other_pending_requests_in_same_primary_club(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $root = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $sibling = Organization::factory()->asRegion()->withParent($root)->withNodePath('100:200:')->create();
        $target = Organization::factory()->asRegion()->withParent($root)->withNodePath('100:300:')->create();

        JoinRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($sibling)
            ->forPrimaryOrganization($root)
            ->create();

        Queue::fake();

        $handler = app(SubmitJoinRequestCommandHandler::class);
        $handler(new SubmitJoinRequestCommand($trooper, $target, null));

        $this->assertSoftDeleted('tt_join_requests', [
            JoinRequest::TROOPER_ID      => $trooper->id,
            JoinRequest::ORGANIZATION_ID => $sibling->id,
        ]);
    }

    public function test_invoke_does_not_cancel_pending_requests_in_different_primary_clubs(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $org_a = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $org_b = Organization::factory()->asOrganization()->withNodePath('200:')->create();

        $sibling_request = JoinRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($org_b)
            ->forPrimaryOrganization($org_b)
            ->create();

        Queue::fake();

        $handler = app(SubmitJoinRequestCommandHandler::class);
        $handler(new SubmitJoinRequestCommand($trooper, $org_a, null));

        $this->assertDatabaseHas('tt_join_requests', [
            JoinRequest::ID              => $sibling_request->id,
            JoinRequest::TROOPER_ID      => $trooper->id,
            JoinRequest::ORGANIZATION_ID => $org_b->id,
            JoinRequest::STATUS          => JoinRequestStatus::PENDING,
        ]);
        $this->assertNull($sibling_request->fresh()->deleted_at);
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
