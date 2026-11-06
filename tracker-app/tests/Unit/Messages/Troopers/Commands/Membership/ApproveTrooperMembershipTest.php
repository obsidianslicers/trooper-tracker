<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Membership;

use App\Enums\MembershipStatus;
use App\Messages\Troopers\Commands\Membership\ApproveTrooperMembership;
use App\Models\Trooper;
use App\Notifications\Troopers\MembershipApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApproveTrooperMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_activates_member_and_notifies(): void
    {
        Notification::fake();
        $trooper = Trooper::factory()->asMember()->asPending()->create();

        (new ApproveTrooperMembership($trooper))->handle();

        $trooper->refresh();
        $this->assertSame(MembershipStatus::ACTIVE, $trooper->membership_status);
        $this->assertNull($trooper->visitor_expires_at);
        Notification::assertSentTo($trooper, MembershipApprovedNotification::class);
    }

    public function test_handle_gives_visitor_six_months_and_clears_expiry_notice(): void
    {
        Notification::fake();
        $this->travelTo(now()->startOfDay());
        $trooper = Trooper::factory()->asVisitor()->asPending()->create([
            Trooper::VISITOR_NOTIFIED_AT => now()->subDay(),
        ]);

        (new ApproveTrooperMembership($trooper))->handle();

        $trooper->refresh();
        $this->assertSame(MembershipStatus::ACTIVE, $trooper->membership_status);
        $this->assertTrue($trooper->visitor_expires_at->equalTo(now()->addMonths(6)));
        $this->assertNull($trooper->visitor_notified_at);
    }
}
