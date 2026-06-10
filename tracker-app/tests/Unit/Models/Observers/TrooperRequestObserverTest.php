<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Observers;

use App\Enums\TrooperRequestStatus;
use App\Models\TrooperRequest;
use App\Models\Observers\TrooperRequestObserver;
use App\Models\Organization;
use App\Models\Trooper;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see TrooperRequestObserver
 */
class TrooperRequestObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_allows_status_transition_from_pending(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $trooper_request->status = TrooperRequestStatus::APPROVED;

        $subject = new TrooperRequestObserver();

        $this->expectNotToPerformAssertions();
        $subject->updating($trooper_request);
    }

    public function test_updating_throws_when_transitioning_from_approved(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->asApproved()
            ->create();

        $trooper_request->status = TrooperRequestStatus::DENIED;

        $subject = new TrooperRequestObserver();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Join request is not pending.');
        $subject->updating($trooper_request);
    }

    public function test_updating_throws_when_transitioning_from_denied(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->asDenied()
            ->create();

        $trooper_request->status = TrooperRequestStatus::APPROVED;

        $subject = new TrooperRequestObserver();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Join request is not pending.');
        $subject->updating($trooper_request);
    }

    public function test_updating_skips_check_when_status_is_not_dirty(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->asApproved()
            ->create();

        $trooper_request->denial_reason = 'updated reason';

        $subject = new TrooperRequestObserver();

        $this->expectNotToPerformAssertions();
        $subject->updating($trooper_request);
    }
}
