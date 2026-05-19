<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\DenyJoinRequestCommand;
use App\Features\Troopers\Commands\DenyJoinRequestCommandHandler;
use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
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

    public function test_invoke_sets_status_to_denied(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $join_request = TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        $handler = app(DenyJoinRequestCommandHandler::class);
        $handler(new DenyJoinRequestCommand($join_request));

        $join_request->refresh();
        $this->assertEquals(MembershipStatus::DENIED, $join_request->membership_status);
    }

    public function test_invoke_sends_denied_notification_to_trooper(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $join_request = TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        $handler = app(DenyJoinRequestCommandHandler::class);
        $handler(new DenyJoinRequestCommand($join_request));

        Notification::assertSentTo($trooper, JoinRequestDeniedNotification::class);
    }
}
