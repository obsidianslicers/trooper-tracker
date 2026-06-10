<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Enums\JoinRequestStatus;
use App\Enums\MembershipStatus;
use App\Features\Troopers\Commands\ApproveTrooperCommand;
use App\Features\Troopers\Commands\ApproveTrooperCommandHandler;
use App\Models\JoinRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Notifications\Troopers\JoinRequestApprovedNotification;
use App\Notifications\Troopers\JoinRequestDeniedNotification;
use App\Notifications\Troopers\MembershipApprovedNotification;
use App\Notifications\Troopers\TrooperDeniedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @see ApproveTrooperCommandHandler
 */
class ApproveTrooperCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_approves_trooper_and_queues_email(): void
    {
        Notification::fake();
        $trooper = Trooper::factory()->asPending()->create();

        $this->assertEquals(MembershipStatus::PENDING, $trooper->membership_status);

        $command = new ApproveTrooperCommand(
            trooper: $trooper,
            is_approved: true
        );
        $handler = app(ApproveTrooperCommandHandler::class);

        $handler($command);

        $trooper->refresh();
        $this->assertEquals(MembershipStatus::ACTIVE, $trooper->membership_status);
        Notification::assertSentTo($trooper, MembershipApprovedNotification::class);
    }

    public function test_invoke_auto_approves_pending_join_requests_without_notification(): void
    {
        Notification::fake();
        $trooper = Trooper::factory()->asPending()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $join_request = JoinRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $handler = app(ApproveTrooperCommandHandler::class);
        $handler(new ApproveTrooperCommand(trooper: $trooper, is_approved: true));

        $join_request->refresh();
        $this->assertEquals(JoinRequestStatus::APPROVED, $join_request->status);
        Notification::assertNotSentTo($trooper, JoinRequestApprovedNotification::class);
    }

    public function test_invoke_denies_trooper_and_sends_denial_notification(): void
    {
        Notification::fake();
        $trooper = Trooper::factory()->asPending()->create();

        $this->assertEquals(MembershipStatus::PENDING, $trooper->membership_status);

        $command = new ApproveTrooperCommand(
            trooper: $trooper,
            is_approved: false
        );
        $handler = app(ApproveTrooperCommandHandler::class);

        $handler($command);

        $trooper->refresh();
        $this->assertEquals(MembershipStatus::DENIED, $trooper->membership_status);
        Notification::assertSentTo($trooper, TrooperDeniedNotification::class);
    }

    public function test_invoke_denies_pending_join_requests_when_trooper_is_denied(): void
    {
        Notification::fake();
        $trooper = Trooper::factory()->asPending()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $join_request = JoinRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $handler = app(ApproveTrooperCommandHandler::class);
        $handler(new ApproveTrooperCommand(
            trooper: $trooper,
            is_approved: false,
            denial_reason: 'Not eligible'
        ));

        $join_request->refresh();

        $this->assertEquals(JoinRequestStatus::DENIED, $join_request->status);
        $this->assertSame('Not eligible', $join_request->denial_reason);
        $this->assertNotNull($join_request->denied_at);
        Notification::assertSentTo($trooper, TrooperDeniedNotification::class);
        Notification::assertNotSentTo($trooper, JoinRequestDeniedNotification::class);
    }
}
