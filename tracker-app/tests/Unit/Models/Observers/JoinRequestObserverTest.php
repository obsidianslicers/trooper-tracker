<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Observers;

use App\Enums\JoinRequestStatus;
use App\Models\JoinRequest;
use App\Models\Observers\JoinRequestObserver;
use App\Models\Organization;
use App\Models\Trooper;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see JoinRequestObserver
 */
class JoinRequestObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_allows_status_transition_from_pending(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $join_request = JoinRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $join_request->status = JoinRequestStatus::APPROVED;

        $subject = new JoinRequestObserver();

        $this->expectNotToPerformAssertions();
        $subject->updating($join_request);
    }

    public function test_updating_throws_when_transitioning_from_approved(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $join_request = JoinRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->asApproved()
            ->create();

        $join_request->status = JoinRequestStatus::DENIED;

        $subject = new JoinRequestObserver();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Join request is not pending.');
        $subject->updating($join_request);
    }

    public function test_updating_throws_when_transitioning_from_denied(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $join_request = JoinRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->asDenied()
            ->create();

        $join_request->status = JoinRequestStatus::APPROVED;

        $subject = new JoinRequestObserver();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Join request is not pending.');
        $subject->updating($join_request);
    }

    public function test_updating_skips_check_when_status_is_not_dirty(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $join_request = JoinRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->asApproved()
            ->create();

        $join_request->denial_reason = 'updated reason';

        $subject = new JoinRequestObserver();

        $this->expectNotToPerformAssertions();
        $subject->updating($join_request);
    }
}
