<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Membership;

use App\Enums\MembershipStatus;
use App\Enums\TrooperRequestStatus;
use App\Messages\Troopers\Commands\Membership\DenyTrooperMembership;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use App\Notifications\Troopers\TrooperDeniedNotification;
use App\Notifications\Troopers\TrooperRequestDeniedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class DenyTrooperMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_denies_trooper_and_all_pending_requests(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $first_organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $second_organization = Organization::factory()->asOrganization()->withNodePath('200:')->create();
        $first_request = $this->createPendingRequest($trooper, $first_organization);
        $second_request = $this->createPendingRequest($trooper, $second_organization);

        (new DenyTrooperMembership($trooper, 'Not eligible.'))->handle();

        $trooper->refresh();
        $first_request->refresh();
        $second_request->refresh();

        $this->assertSame(MembershipStatus::DENIED, $trooper->membership_status);
        $this->assertSame(TrooperRequestStatus::DENIED, $first_request->status);
        $this->assertSame(TrooperRequestStatus::DENIED, $second_request->status);
        $this->assertSame('Not eligible.', $first_request->denial_reason);
        $this->assertSame('Not eligible.', $second_request->denial_reason);
    }

    public function test_handle_leaves_non_pending_requests_unchanged(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $approved_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->asApproved()
            ->create();

        (new DenyTrooperMembership($trooper))->handle();

        $approved_request->refresh();

        $this->assertSame(TrooperRequestStatus::APPROVED, $approved_request->status);
        $this->assertNull($approved_request->denial_reason);
    }

    public function test_handle_sends_only_the_trooper_denial_notification(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $this->createPendingRequest($trooper, $organization);

        (new DenyTrooperMembership($trooper, 'Not eligible.'))->handle();

        Notification::assertSentTo($trooper, TrooperDeniedNotification::class);
        Notification::assertNotSentTo($trooper, TrooperRequestDeniedNotification::class);
    }

    private function createPendingRequest(Trooper $trooper, Organization $organization): TrooperRequest
    {
        return TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->asPending()
            ->create();
    }
}