<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Enums\ShiftAttendanceOutcome;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperNotification;
use App\Notifications\Events\EventShiftCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTroopsToConfirm;
use Tests\TestCase;

class ConfirmShiftAttendanceControllerTest extends TestCase
{
    use BuildsTroopsToConfirm;
    use RefreshDatabase;

    public function test_i_was_there_records_attended_and_goes_back(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $troop = $this->troopToConfirm($trooper);

        $response = $this->actingAs($trooper)
            ->from(route('faq'))
            ->post(route('events.confirm-attendance', $troop), ['status' => EventTrooperStatus::ATTENDED->value]);

        $response->assertRedirect(route('faq'));
        $response->assertSessionHas('success');
        $this->assertSame(EventTrooperStatus::ATTENDED, $troop->fresh()->status);
    }

    public function test_couldnt_make_it_records_unable_to_attend(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $troop = $this->troopToConfirm($trooper, EventTrooperStatus::TENTATIVE);

        $this->actingAs($trooper)
            ->post(route('events.confirm-attendance', $troop), ['status' => EventTrooperStatus::UNABLE_TO_ATTEND->value])
            ->assertRedirect(route('account.push-notifications'));

        $this->assertSame(EventTrooperStatus::UNABLE_TO_ATTEND, $troop->fresh()->status);
    }

    public function test_answering_deletes_the_reminder(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $troop = $this->troopToConfirm($trooper);
        $other = $this->troopToConfirm($trooper, event_name: 'Comic Con');

        foreach ([$troop, $other] as $event_trooper)
        {
            TrooperNotification::factory()->forTrooper($trooper)->create([
                TrooperNotification::TYPE => EventShiftCompletedNotification::class,
                TrooperNotification::DATA => ['title' => 'Event Shift Completed', EventShiftCompletedNotification::EVENT_TROOPER_ID => $event_trooper->id],
            ]);
        }

        $this->actingAs($trooper)
            ->post(route('events.confirm-attendance', $troop), ['status' => EventTrooperStatus::ATTENDED->value]);

        $this->assertSame(
            [$other->id],
            TrooperNotification::all()->map(fn ($n) => $n->data[EventShiftCompletedNotification::EVENT_TROOPER_ID])->all(),
        );
    }

    public function test_a_trooper_needing_verification_is_sent_to_a_moderator(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->asUntrusted()->create();
        $troop = $this->troopToConfirm($trooper);

        $this->actingAs($trooper)
            ->post(route('events.confirm-attendance', $troop), ['status' => EventTrooperStatus::ATTENDED->value])
            ->assertSessionHas('success', __('events.'.ShiftAttendanceOutcome::VERIFICATION_MESSAGE));

        $this->assertSame(EventTrooperStatus::AWAITING_VERIFICATION, $troop->fresh()->status);
    }

    public function test_a_costume_crediting_more_than_one_club_goes_to_the_club_choice(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $troop = $this->troopToConfirm($trooper);
        $second_club = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($second_club)->asMember()->create();
        $troop->forceFill([EventTrooper::COSTUME_ORGANIZATION_IDS => [$troop->event_shift->event->organization_id, $second_club->id]])->saveQuietly();

        $response = $this->actingAs($trooper)
            ->post(route('events.confirm-attendance', $troop), ['status' => EventTrooperStatus::ATTENDED->value]);

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith(url("/events/complete/{$troop->id}/"), $location);
        $this->assertSame(EventTrooperStatus::GOING, $troop->fresh()->status);

        $this->actingAs($trooper)->get($location)
            ->assertViewIs('pages.events.shift-complete-club-select');
    }

    public function test_past_the_grace_period_nothing_changes(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $troop = $this->troopToConfirm($trooper, ended_at: now()->subDays(40));

        $this->actingAs($trooper)
            ->post(route('events.confirm-attendance', $troop), ['status' => EventTrooperStatus::ATTENDED->value])
            ->assertSessionHas('danger');

        $this->assertSame(EventTrooperStatus::GOING, $troop->fresh()->status);
    }

    public function test_only_the_trooper_who_signed_up_can_answer(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $troop = $this->troopToConfirm(Trooper::factory()->asActive()->create());

        $this->actingAs($trooper)
            ->post(route('events.confirm-attendance', $troop), ['status' => EventTrooperStatus::ATTENDED->value])
            ->assertForbidden();

        $this->assertSame(EventTrooperStatus::GOING, $troop->fresh()->status);
    }

    public function test_only_the_two_answers_are_accepted(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $troop = $this->troopToConfirm($trooper);

        $this->actingAs($trooper)
            ->post(route('events.confirm-attendance', $troop), ['status' => EventTrooperStatus::CANCELLED->value])
            ->assertSessionHasErrors('status');

        $this->assertSame(EventTrooperStatus::GOING, $troop->fresh()->status);
    }
}
