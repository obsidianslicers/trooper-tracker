<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Enums\JoinRequestStatus;
use App\Features\Troopers\Commands\DenyJoinRequestCommand;
use App\Features\Troopers\Commands\DenyJoinRequestCommandHandler;
use App\Models\JoinRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Notifications\Troopers\JoinRequestDeniedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @see DenyJoinRequestCommandHandler
 */
class DenyJoinRequestCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_marks_join_request_as_denied(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $join_request = JoinRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $handler = app(DenyJoinRequestCommandHandler::class);
        $handler(new DenyJoinRequestCommand($join_request));

        $join_request->refresh();
        $this->assertEquals(JoinRequestStatus::DENIED, $join_request->status);
        $this->assertNotNull($join_request->denied_at);
    }

    public function test_invoke_persists_denial_reason_on_the_record(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $join_request = JoinRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $handler = app(DenyJoinRequestCommandHandler::class);
        $handler(new DenyJoinRequestCommand($join_request, 'Membership not yet verified.'));

        $join_request->refresh();
        $this->assertEquals('Membership not yet verified.', $join_request->denial_reason);
    }

    public function test_invoke_does_not_create_membership_or_assignment(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $join_request = JoinRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $handler = app(DenyJoinRequestCommandHandler::class);
        $handler(new DenyJoinRequestCommand($join_request));

        $this->assertDatabaseEmpty('tt_trooper_organizations');
        $this->assertDatabaseEmpty('tt_trooper_assignments');
    }

    public function test_invoke_sends_denied_notification_to_trooper(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $join_request = JoinRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $handler = app(DenyJoinRequestCommandHandler::class);
        $handler(new DenyJoinRequestCommand($join_request));

        Notification::assertSentTo($trooper, JoinRequestDeniedNotification::class);
    }
}
