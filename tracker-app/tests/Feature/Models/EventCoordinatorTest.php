<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCoordinatorTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $creator;

    private Event $event;

    private EventShift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creator = Trooper::factory()->create();
        $this->event = Event::factory()->withCreatedByTrooper($this->creator)->create();
        $this->shift = EventShift::factory()->forEvent($this->event)->create();
    }

    public function test_the_creator_coordinates_until_someone_is_picked(): void
    {
        $this->assertTrue($this->event->eventCoordinator()->is($this->creator));

        $picked = $this->signUp(EventTrooperStatus::GOING)->trooper;
        $this->event->update([Event::COORDINATOR_TROOPER_ID => $picked->id]);

        $this->assertTrue($this->event->fresh()->eventCoordinator()->is($picked));
    }

    public function test_candidates_are_the_troopers_going_or_attended(): void
    {
        $going = $this->signUp(EventTrooperStatus::GOING)->trooper;
        $attended = $this->signUp(EventTrooperStatus::ATTENDED)->trooper;
        $verifying = $this->signUp(EventTrooperStatus::AWAITING_VERIFICATION)->trooper;
        $this->signUp(EventTrooperStatus::TENTATIVE);
        $this->signUp(EventTrooperStatus::STAND_BY);
        $this->signUp(EventTrooperStatus::CANCELLED);
        $other_event = EventShift::factory()->create();
        EventTrooper::factory()->forEventShift($other_event)->create([EventTrooper::STATUS => EventTrooperStatus::GOING]);

        $candidates = $this->event->coordinatorCandidates()->pluck(Trooper::ID)->all();

        $this->assertEqualsCanonicalizing([$going->id, $attended->id, $verifying->id], $candidates);
    }

    public function test_candidates_are_active_event_coordinators_and_up(): void
    {
        $moderator = $this->signUp(EventTrooperStatus::GOING)->trooper;
        $moderator->update([Trooper::MEMBERSHIP_ROLE => MembershipRole::MODERATOR]);
        $member = $this->signUp(EventTrooperStatus::GOING)->trooper;
        $member->update([Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER]);
        $retired = $this->signUp(EventTrooperStatus::GOING)->trooper;
        $retired->update([Trooper::MEMBERSHIP_STATUS => MembershipStatus::RETIRED]);

        $this->assertSame([$moderator->id], $this->event->coordinatorCandidates()->pluck(Trooper::ID)->all());
    }

    public function test_a_coordinator_who_stops_going_goes_back_to_the_creator(): void
    {
        $sign_up = $this->pick(EventTrooperStatus::GOING);

        $sign_up->update([EventTrooper::STATUS => EventTrooperStatus::CANCELLED]);

        $this->assertNull($this->event->fresh()->coordinator_trooper_id);
        $this->assertTrue($this->event->fresh()->eventCoordinator()->is($this->creator));
    }

    public function test_a_coordinator_marked_attended_or_awaiting_verification_stays(): void
    {
        $sign_up = $this->pick(EventTrooperStatus::GOING);

        $sign_up->update([EventTrooper::STATUS => EventTrooperStatus::AWAITING_VERIFICATION]);
        $sign_up->update([EventTrooper::STATUS => EventTrooperStatus::ATTENDED]);

        $this->assertSame($sign_up->trooper_id, $this->event->fresh()->coordinator_trooper_id);
    }

    public function test_a_coordinator_still_going_on_another_shift_stays(): void
    {
        $sign_up = $this->pick(EventTrooperStatus::GOING);
        $evening = EventShift::factory()->forEvent($this->event)->create();
        EventTrooper::factory()->forEventShift($evening)->forTrooper($sign_up->trooper)->create([
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $sign_up->update([EventTrooper::STATUS => EventTrooperStatus::NO_SHOW]);

        $this->assertSame($sign_up->trooper_id, $this->event->fresh()->coordinator_trooper_id);
    }

    public function test_removing_the_coordinators_sign_up_or_shift_goes_back_to_the_creator(): void
    {
        $sign_up = $this->pick(EventTrooperStatus::ATTENDED);
        $sign_up->delete();
        $this->assertNull($this->event->fresh()->coordinator_trooper_id);

        $this->pick(EventTrooperStatus::ATTENDED);
        $this->shift->delete();
        $this->assertNull($this->event->fresh()->coordinator_trooper_id);
    }

    public function test_someone_elses_status_change_leaves_the_coordinator(): void
    {
        $sign_up = $this->pick(EventTrooperStatus::GOING);
        $other = $this->signUp(EventTrooperStatus::GOING);

        $other->update([EventTrooper::STATUS => EventTrooperStatus::CANCELLED]);

        $this->assertSame($sign_up->trooper_id, $this->event->fresh()->coordinator_trooper_id);
    }

    private function signUp(EventTrooperStatus $status): EventTrooper
    {
        return EventTrooper::factory()->forEventShift($this->shift)
            ->forTrooper(Trooper::factory()->asEventCoordinator()->create())
            ->create([EventTrooper::STATUS => $status]);
    }

    private function pick(EventTrooperStatus $status): EventTrooper
    {
        $sign_up = $this->signUp($status);
        $this->event->update([Event::COORDINATOR_TROOPER_ID => $sign_up->trooper_id]);

        return $sign_up;
    }
}
