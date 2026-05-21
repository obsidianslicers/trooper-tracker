<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Notifications\Troopers\VisitorAccessExpiredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExpireVisitorAccessCommandTest extends TestCase
{
    use RefreshDatabase;

    private function makeExpiredVisitor(array $overrides = []): Trooper
    {
        return Trooper::factory()->create(array_merge([
            Trooper::MEMBERSHIP_ROLE    => MembershipRole::VISITOR,
            Trooper::MEMBERSHIP_STATUS  => MembershipStatus::ACTIVE,
            Trooper::VISITOR_EXPIRES_AT => now()->subMonth(),
            Trooper::VISITOR_NOTIFIED_AT => null,
        ], $overrides));
    }

    public function test_notifies_expired_visitors_and_sets_notified_at(): void
    {
        Notification::fake();

        $trooper = $this->makeExpiredVisitor();

        $this->artisan('tracker:expire-visitor-access')->assertExitCode(0);

        $this->assertNotNull($trooper->fresh()->{Trooper::VISITOR_NOTIFIED_AT});
        Notification::assertSentTo($trooper, VisitorAccessExpiredNotification::class);
    }

    public function test_does_not_notify_already_notified_visitors(): void
    {
        Notification::fake();

        $trooper = $this->makeExpiredVisitor([
            Trooper::VISITOR_NOTIFIED_AT => now()->subWeek(),
        ]);

        $this->artisan('tracker:expire-visitor-access')->assertExitCode(0);

        Notification::assertNothingSent();
        $this->assertEquals(
            $trooper->{Trooper::VISITOR_NOTIFIED_AT}->toDateString(),
            $trooper->fresh()->{Trooper::VISITOR_NOTIFIED_AT}->toDateString()
        );
    }

    public function test_does_not_notify_non_expired_visitors(): void
    {
        Notification::fake();

        $this->makeExpiredVisitor([
            Trooper::VISITOR_EXPIRES_AT => now()->addMonths(3),
        ]);

        $this->artisan('tracker:expire-visitor-access')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_outputs_count_of_notified_visitors(): void
    {
        Notification::fake();

        $this->makeExpiredVisitor();

        $this->artisan('tracker:expire-visitor-access')
            ->expectsOutput('Notified 1 visitor(s) of expired access.')
            ->assertExitCode(0);
    }
}
