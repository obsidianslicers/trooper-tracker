<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands\Corrections;

use App\Enums\EventTrooperStatus;
use App\Enums\MembershipStatus;
use App\Enums\TroopCorrectionStatus;
use App\Jobs\SendTroopVerificationNotificationsJob;
use App\Messages\Events\Commands\Corrections\ConfirmTroopCorrection;
use App\Messages\Events\Commands\Corrections\DenyTroopCorrection;
use App\Messages\Events\Commands\Corrections\FollowUpTroopCorrections;
use App\Messages\Events\Commands\Corrections\RequestTroopCorrection;
use App\Messages\Events\Commands\Corrections\SettleTroopCorrection;
use App\Messages\Events\Commands\Verification\ApproveTroopCredit;
use App\Messages\Events\Commands\Verification\RejectTroopCredit;
use App\Messages\Events\Queries\Verification\GetTroopsAwaitingVerification;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\TroopCorrection;
use App\Models\Trooper;
use App\Notifications\Events\TroopCorrectionDecidedNotification;
use App\Notifications\Events\TroopCorrectionRequestedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class TroopCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
    }

    public function test_request_waits_for_the_event_coordinator(): void
    {
        Notification::fake();
        $coordinator = Trooper::factory()->create();
        $shift = $this->closedShift($coordinator);
        $trooper = Trooper::factory()->create();

        $correction = $this->request($trooper, $shift->event);

        $this->assertSame(TroopCorrectionStatus::AWAITING_COORDINATOR, $correction->status);
        $this->assertSame($coordinator->id, $correction->coordinator_trooper_id);
        $this->assertNull($correction->previous_status);

        $event_trooper = $correction->event_trooper;
        $this->assertSame($shift->id, $event_trooper->event_shift_id);
        $this->assertSame(EventTrooperStatus::AWAITING_VERIFICATION, $event_trooper->status);
        $this->assertSame([$this->organization->id], $event_trooper->requested_organization_ids);

        Notification::assertSentTo($coordinator, TroopCorrectionRequestedNotification::class);
    }

    public function test_moderators_do_not_see_a_request_the_coordinator_has_not_answered(): void
    {
        Notification::fake();
        $admin = Trooper::factory()->asSystemOwner()->create();
        $shift = $this->closedShift(Trooper::factory()->create());

        $this->request(Trooper::factory()->create(), $shift->event);

        $this->assertCount(0, GetTroopsAwaitingVerification::call(moderator: $admin));
    }

    public function test_request_goes_to_moderators_when_the_event_has_no_coordinator(): void
    {
        Queue::fake();
        $admin = Trooper::factory()->asSystemOwner()->create();
        $shift = $this->closedShift(null);

        $correction = $this->request(Trooper::factory()->create(), $shift->event);

        $this->assertSame(TroopCorrectionStatus::AWAITING_MODERATOR, $correction->fresh()->status);
        $this->assertNotNull($correction->fresh()->escalated_at);
        Queue::assertPushed(SendTroopVerificationNotificationsJob::class);

        $rows = GetTroopsAwaitingVerification::call(moderator: $admin);
        $this->assertCount(1, $rows);
        $this->assertSame('I held the door for the parade.', $rows->first()->pending_troop_correction->details);
    }

    public function test_request_goes_to_moderators_when_the_trooper_coordinated_the_event(): void
    {
        Queue::fake();
        $trooper = Trooper::factory()->create();
        $shift = $this->closedShift($trooper);

        $correction = $this->request($trooper, $shift->event);

        $this->assertSame(TroopCorrectionStatus::AWAITING_MODERATOR, $correction->fresh()->status);
        $this->assertNull($correction->fresh()->coordinator_trooper_id);
    }

    public function test_request_from_a_flagged_trooper_goes_to_moderators(): void
    {
        Queue::fake();
        $shift = $this->closedShift(Trooper::factory()->create());

        $correction = $this->request(Trooper::factory()->asUntrusted()->create(), $shift->event);

        $this->assertSame(TroopCorrectionStatus::AWAITING_MODERATOR, $correction->fresh()->status);
    }

    public function test_request_refuses_an_event_that_is_not_completed(): void
    {
        $shift = $this->closedShift(null);
        $shift->event->update([Event::STATUS => 'open']);

        $this->expectException(RuntimeException::class);

        $this->request(Trooper::factory()->create(), $shift->event->fresh());
    }

    public function test_request_refuses_a_shift_the_trooper_already_has_credit_for(): void
    {
        $shift = $this->closedShift(null);
        $trooper = Trooper::factory()->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create();

        $this->expectException(RuntimeException::class);

        $this->request($trooper, $shift->event);
    }

    public function test_coordinator_confirm_gives_the_credit(): void
    {
        Notification::fake();
        $coordinator = Trooper::factory()->create();
        $shift = $this->closedShift($coordinator);
        $trooper = Trooper::factory()->create();
        $correction = $this->request($trooper, $shift->event);

        ConfirmTroopCorrection::call(troop_correction: $correction, coordinator: $coordinator);

        $correction->refresh();
        $this->assertSame(TroopCorrectionStatus::APPROVED, $correction->status);
        $this->assertSame($coordinator->id, $correction->decided_by_trooper_id);
        $this->assertNotNull($correction->decided_at);

        $event_trooper = $correction->event_trooper;
        $this->assertSame(EventTrooperStatus::ATTENDED, $event_trooper->status);
        $this->assertSame([$this->organization->id], $event_trooper->costume_organization_ids);
        $this->assertNull($event_trooper->requested_organization_ids);

        Notification::assertSentTo($trooper, TroopCorrectionDecidedNotification::class);
    }

    public function test_only_the_coordinator_asked_may_confirm(): void
    {
        Notification::fake();
        $shift = $this->closedShift(Trooper::factory()->create());
        $correction = $this->request(Trooper::factory()->create(), $shift->event);

        $this->expectException(RuntimeException::class);

        ConfirmTroopCorrection::call(troop_correction: $correction, coordinator: Trooper::factory()->create());
    }

    public function test_coordinator_deny_hands_it_to_moderators(): void
    {
        Notification::fake();
        Queue::fake();
        $coordinator = Trooper::factory()->create();
        $shift = $this->closedShift($coordinator);
        $correction = $this->request(Trooper::factory()->create(), $shift->event);

        DenyTroopCorrection::call(troop_correction: $correction, coordinator: $coordinator);

        $correction->refresh();
        $this->assertSame(TroopCorrectionStatus::AWAITING_MODERATOR, $correction->status);
        $this->assertNotNull($correction->coordinator_denied_at);
        $this->assertSame(EventTrooperStatus::AWAITING_VERIFICATION, $correction->event_trooper->status);
        Queue::assertPushed(SendTroopVerificationNotificationsJob::class);
    }

    public function test_moderator_approval_decides_the_request(): void
    {
        Notification::fake();
        Queue::fake();
        $moderator = Trooper::factory()->asSystemOwner()->create();
        $shift = $this->closedShift(null);
        $correction = $this->request(Trooper::factory()->create(), $shift->event);

        ApproveTroopCredit::call(
            event_trooper: $correction->event_trooper,
            organization_id: $this->organization->id,
            moderator: $moderator,
        );

        $correction->refresh();
        $this->assertSame(TroopCorrectionStatus::APPROVED, $correction->status);
        $this->assertSame($moderator->id, $correction->decided_by_trooper_id);
        $this->assertSame(EventTrooperStatus::ATTENDED, $correction->event_trooper->status);
    }

    public function test_moderator_rejection_removes_a_sign_up_the_request_made(): void
    {
        Notification::fake();
        Queue::fake();
        $shift = $this->closedShift(null);
        $correction = $this->request(Trooper::factory()->create(), $shift->event);

        RejectTroopCredit::call(event_trooper: $correction->event_trooper, organization_id: $this->organization->id);

        $correction->refresh();
        $this->assertSame(TroopCorrectionStatus::DENIED, $correction->status);
        $this->assertTrue($correction->event_trooper->trashed());
    }

    public function test_settling_returns_the_request_it_decided_for_plugins(): void
    {
        Notification::fake();
        Queue::fake();
        $shift = $this->closedShift(null);
        $correction = $this->request(Trooper::factory()->create(), $shift->event);
        $event_trooper = $correction->event_trooper;

        $this->assertNull(SettleTroopCorrection::call(event_trooper: $event_trooper));

        $event_trooper->rejectRequestedOrganization($this->organization->id);
        $event_trooper->save();
        $settled = SettleTroopCorrection::call(event_trooper: $event_trooper);

        $this->assertSame($correction->id, $settled?->id);
        $this->assertSame(TroopCorrectionStatus::DENIED, $settled->status);
    }

    public function test_a_request_still_names_its_event_after_the_event_is_deleted(): void
    {
        Notification::fake();
        Queue::fake();
        $shift = $this->closedShift(null);
        $correction = $this->request(Trooper::factory()->create(), $shift->event);

        $shift->event->delete();

        $this->assertSame($shift->event->name, $correction->fresh()->event->name);
    }

    public function test_moderator_rejection_puts_back_a_cancelled_sign_up(): void
    {
        Notification::fake();
        Queue::fake();
        $shift = $this->closedShift(null);
        $trooper = Trooper::factory()->create();
        $cancelled = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)
            ->create([EventTrooper::STATUS => EventTrooperStatus::CANCELLED]);

        $correction = $this->request($trooper, $shift->event);

        $this->assertSame($cancelled->id, $correction->event_trooper_id);
        $this->assertSame(EventTrooperStatus::CANCELLED, $correction->previous_status);

        RejectTroopCredit::call(event_trooper: $correction->event_trooper, organization_id: $this->organization->id);

        $this->assertSame(EventTrooperStatus::CANCELLED, $cancelled->fresh()->status);
        $this->assertFalse($cancelled->fresh()->trashed());
    }

    public function test_request_restores_a_removed_sign_up(): void
    {
        Notification::fake();
        $shift = $this->closedShift(Trooper::factory()->create());
        $trooper = Trooper::factory()->create();
        $removed = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asGoing()->create();
        $removed->delete();

        $correction = $this->request($trooper, $shift->event);

        $this->assertSame($removed->id, $correction->event_trooper_id);
        $this->assertNull($correction->previous_status);
        $this->assertFalse($removed->fresh()->trashed());
    }

    public function test_follow_up_reminds_the_coordinator_once_after_a_week(): void
    {
        Notification::fake();
        $coordinator = Trooper::factory()->create();
        $shift = $this->closedShift($coordinator);
        $correction = $this->request(Trooper::factory()->create(), $shift->event);
        $this->age($correction, days: 8);

        $first = FollowUpTroopCorrections::call();
        $second = FollowUpTroopCorrections::call();

        $this->assertSame(['reminded' => 1, 'escalated' => 0], $first);
        $this->assertSame(['reminded' => 0, 'escalated' => 0], $second);
        $this->assertNotNull($correction->fresh()->reminded_at);
        Notification::assertSentToTimes($coordinator, TroopCorrectionRequestedNotification::class, 2);
    }

    public function test_follow_up_hands_it_to_moderators_after_two_weeks(): void
    {
        Notification::fake();
        Queue::fake();
        $shift = $this->closedShift(Trooper::factory()->create());
        $correction = $this->request(Trooper::factory()->create(), $shift->event);
        $this->age($correction, days: 15);

        $counts = FollowUpTroopCorrections::call();

        $this->assertSame(['reminded' => 0, 'escalated' => 1], $counts);
        $this->assertSame(TroopCorrectionStatus::AWAITING_MODERATOR, $correction->fresh()->status);
    }

    public function test_follow_up_hands_it_to_moderators_when_the_coordinator_left(): void
    {
        Notification::fake();
        Queue::fake();
        $coordinator = Trooper::factory()->create();
        $shift = $this->closedShift($coordinator);
        $correction = $this->request(Trooper::factory()->create(), $shift->event);
        $coordinator->update([Trooper::MEMBERSHIP_STATUS => MembershipStatus::RETIRED]);
        $this->age($correction, days: 8);

        $counts = FollowUpTroopCorrections::call();

        $this->assertSame(['reminded' => 0, 'escalated' => 1], $counts);
    }

    public function test_can_be_requested_for_a_completed_event_without_credit(): void
    {
        $shift = $this->closedShift(null);
        $trooper = Trooper::factory()->create();

        $this->assertTrue(TroopCorrection::canBeRequestedFor($shift->event, $trooper));

        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create();

        $this->assertFalse(TroopCorrection::canBeRequestedFor($shift->event->fresh(), $trooper));
    }

    private function closedShift(?Trooper $coordinator): EventShift
    {
        $factory = Event::factory()
            ->withOrganization($this->organization)
            ->asClosed()
            ->withEventStart(Carbon::now()->subDays(60)->setTime(10, 0))
            ->withEventEnd(Carbon::now()->subDays(60)->setTime(14, 0));

        $event = ($coordinator ? $factory->withCreatedByTrooper($coordinator) : $factory)->create();

        return EventShift::factory()->forEvent($event)->asClosed()
            ->withShiftStartsAt(Carbon::now()->subDays(60)->setTime(10, 0))
            ->withShiftEndsAt(Carbon::now()->subDays(60)->setTime(14, 0))
            ->create();
    }

    private function request(Trooper $trooper, Event $event): TroopCorrection
    {
        return RequestTroopCorrection::call(
            trooper: $trooper,
            event: $event,
            details: 'I held the door for the parade.',
        );
    }

    private function age(TroopCorrection $correction, int $days): void
    {
        TroopCorrection::whereKey($correction->id)->update([TroopCorrection::CREATED_AT => now()->subDays($days)]);
    }
}
