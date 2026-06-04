<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\ApproveTrooperCommand;
use App\Features\Troopers\Commands\ApproveTrooperCommandHandler;
use App\Enums\MembershipStatus;
use App\Models\Trooper;
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
}
