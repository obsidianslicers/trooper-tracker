<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Features\Troopers\Commands\NotifyExpiredVisitorCommand;
use App\Features\Troopers\Commands\NotifyExpiredVisitorCommandHandler;
use App\Models\Trooper;
use App\Notifications\Troopers\VisitorAccessExpiredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotifyExpiredVisitorCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sets_visitor_notified_at_on_trooper(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE    => MembershipRole::VISITOR,
            Trooper::MEMBERSHIP_STATUS  => MembershipStatus::ACTIVE,
            Trooper::VISITOR_EXPIRES_AT => now()->subMonth(),
            Trooper::VISITOR_NOTIFIED_AT => null,
        ]);

        $handler = app(NotifyExpiredVisitorCommandHandler::class);
        $handler(new NotifyExpiredVisitorCommand($trooper));

        $this->assertNotNull($trooper->fresh()->{Trooper::VISITOR_NOTIFIED_AT});
    }

    public function test_sends_visitor_access_expired_notification(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE    => MembershipRole::VISITOR,
            Trooper::MEMBERSHIP_STATUS  => MembershipStatus::ACTIVE,
            Trooper::VISITOR_EXPIRES_AT => now()->subMonth(),
            Trooper::VISITOR_NOTIFIED_AT => null,
        ]);

        $handler = app(NotifyExpiredVisitorCommandHandler::class);
        $handler(new NotifyExpiredVisitorCommand($trooper));

        Notification::assertSentTo($trooper, VisitorAccessExpiredNotification::class);
    }
}
