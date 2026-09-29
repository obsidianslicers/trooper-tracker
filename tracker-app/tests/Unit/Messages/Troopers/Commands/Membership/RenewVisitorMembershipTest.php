<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Membership;

use App\Enums\MembershipStatus;
use App\Jobs\SendTrooperRegisteredNotificationsJob;
use App\Messages\Troopers\Commands\Membership\RenewVisitorMembership;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class RenewVisitorMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_marks_visitor_membership_pending_and_dispatches_registration_notification(): void
    {
        Queue::fake();

        $trooper = Trooper::factory()->asVisitor()->create();

        (new RenewVisitorMembership($trooper))->handle();

        $this->assertDatabaseHas('tt_troopers', [
            Trooper::ID => $trooper->id,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::PENDING->value,
        ]);
        Queue::assertPushed(SendTrooperRegisteredNotificationsJob::class);
    }
}