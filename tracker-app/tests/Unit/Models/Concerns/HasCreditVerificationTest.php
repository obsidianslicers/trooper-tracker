<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCostumeReviews;
use Tests\TestCase;

class HasCreditVerificationTest extends TestCase
{
    use BuildsCostumeReviews;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpClubs();
    }

    public function test_request_verification_parks_credit(): void
    {
        $subject = $this->eventTrooperFor($this->memberOf($this->garrison));
        $subject->organization_id = $this->garrison->id;

        $subject->requestVerification([$this->garrison->id, $this->other_club->id, $this->garrison->id]);

        $this->assertSame(EventTrooperStatus::AWAITING_VERIFICATION, $subject->status);
        $this->assertSame([$this->garrison->id, $this->other_club->id], $subject->requested_organization_ids);
        $this->assertNull($subject->costume_organization_ids);
        $this->assertNull($subject->organization_id);
        $this->assertTrue($subject->isAwaitingVerification());
    }

    public function test_approving_one_club_credits_it_and_keeps_the_other_waiting(): void
    {
        $subject = $this->eventTrooperFor($this->memberOf($this->garrison));
        $subject->requestVerification([$this->garrison->id, $this->other_club->id]);

        $subject->approveRequestedOrganization($this->garrison->id);

        $this->assertSame(EventTrooperStatus::ATTENDED, $subject->status);
        $this->assertSame([$this->garrison->id], $subject->costume_organization_ids);
        $this->assertSame([$this->other_club->id], $subject->requested_organization_ids);
    }

    public function test_rejecting_every_club_makes_it_a_no_show(): void
    {
        $subject = $this->eventTrooperFor($this->memberOf($this->garrison));
        $subject->requestVerification([$this->garrison->id]);

        $subject->rejectRequestedOrganization($this->garrison->id);

        $this->assertSame(EventTrooperStatus::NO_SHOW, $subject->status);
        $this->assertNull($subject->requested_organization_ids);
        $this->assertFalse($subject->isAwaitingVerification());
    }

    public function test_rejecting_the_last_club_after_an_approval_keeps_it_attended(): void
    {
        $subject = $this->eventTrooperFor($this->memberOf($this->garrison));
        $subject->requestVerification([$this->garrison->id, $this->other_club->id]);
        $subject->approveRequestedOrganization($this->garrison->id);

        $subject->rejectRequestedOrganization($this->other_club->id);

        $this->assertSame(EventTrooperStatus::ATTENDED, $subject->status);
        $this->assertSame([$this->garrison->id], $subject->costume_organization_ids);
    }

    public function test_moderator_only_verifies_clubs_in_their_tree_for_troopers_they_cover(): void
    {
        $trooper = $this->memberOf($this->garrison);
        $this->memberOf($this->other_club, $trooper);
        $subject = $this->eventTrooperFor($trooper);
        $subject->requestVerification([$this->garrison->id, $this->other_club->id]);

        $club_moderator = $this->moderatorOf($this->garrison);
        $other_moderator = $this->moderatorOf($this->other_club);
        $stranger = $this->moderatorOf(Organization::factory()->asOrganization()->create());

        $this->assertSame([$this->garrison->id], $subject->verifiableOrganizationIdsFor($club_moderator));
        $this->assertSame([$this->other_club->id], $subject->verifiableOrganizationIdsFor($other_moderator));
        $this->assertSame([], $subject->verifiableOrganizationIdsFor($stranger));
    }

    public function test_nobody_verifies_their_own_troop(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
        $subject = $this->eventTrooperFor($this->memberOf($this->garrison, $admin));
        $subject->requestVerification([$this->garrison->id]);

        $this->assertSame([], $subject->verifiableOrganizationIdsFor($admin));
        $this->assertSame(
            [$this->garrison->id],
            $subject->verifiableOrganizationIdsFor(Trooper::factory()->asSystemOwner()->create())
        );
    }

    private function eventTrooperFor(Trooper $trooper): EventTrooper
    {
        $event = Event::factory()->withOrganization($this->club)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        return EventTrooper::factory()->forEventShift($event_shift)->forTrooper($trooper)->asGoing()->create();
    }
}
