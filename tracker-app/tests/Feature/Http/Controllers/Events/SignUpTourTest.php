<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Support\SignUpTour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The event page's "How sign-up works" tour (pages/events/inc/signup-tour.blade.php) and who
 * it starts by itself for (tracker.signup_tour).
 */
class SignUpTourTest extends TestCase
{
    use RefreshDatabase;

    public function test_by_default_it_starts_by_itself_for_a_trooper_who_has_not_attended_a_troop(): void
    {
        $response = $this->showEvent($this->trooper());

        $response->assertOk();
        $response->assertSee('data-autostart="true"', false);
        $response->assertSee('How sign-up works');
        $response->assertSeeInOrder(['Example shift', 'How signing up works', 'Sign up for each shift', "Then you're on the roster", 'After the troop']);
        $response->assertDontSee('Read the mission brief first');
    }

    public function test_by_default_it_waits_for_the_button_once_a_trooper_has_attended(): void
    {
        foreach ([EventTrooperStatus::ATTENDED, EventTrooperStatus::AWAITING_VERIFICATION] as $status)
        {
            $trooper = $this->trooper();
            EventTrooper::factory()->forTrooper($trooper)->create([EventTrooper::STATUS => $status]);

            $response = $this->showEvent($trooper);

            $response->assertSee('data-autostart="false"', false);
            $response->assertSee('How sign-up works');
        }
    }

    public function test_a_sign_up_they_did_not_attend_still_counts_as_new(): void
    {
        $trooper = $this->trooper();
        EventTrooper::factory()->forTrooper($trooper)->create([EventTrooper::STATUS => EventTrooperStatus::NO_SHOW]);

        $this->showEvent($trooper)->assertSee('data-autostart="true"', false);
    }

    public function test_everyone_starts_it_whatever_they_have_attended(): void
    {
        config(['tracker.signup_tour' => SignUpTour::EVERYONE]);
        $trooper = $this->trooper();
        EventTrooper::factory()->forTrooper($trooper)->create([EventTrooper::STATUS => EventTrooperStatus::ATTENDED]);

        $this->showEvent($trooper)->assertSee('data-autostart="true"', false);
    }

    public function test_off_leaves_only_the_button(): void
    {
        config(['tracker.signup_tour' => SignUpTour::OFF]);

        $response = $this->showEvent($this->trooper());

        $response->assertSee('data-autostart="false"', false);
        $response->assertSee('How sign-up works');
        $response->assertSee('Example shift');
    }

    public function test_it_waits_for_the_button_when_no_shift_is_open(): void
    {
        config(['tracker.signup_tour' => SignUpTour::EVERYONE]);
        $event = Event::factory()->create();
        EventShift::factory()->forEvent($event)->asClosed()->create();

        $response = $this->actingAs($this->trooper())->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertSee('data-autostart="false"', false);
    }

    public function test_it_is_remembered_per_trooper(): void
    {
        $trooper = $this->trooper();

        $this->showEvent($trooper)->assertSee('tt.signupTourSeen.'.$trooper->id);
    }

    public function test_it_starts_with_the_mission_brief_when_it_must_be_acknowledged(): void
    {
        $event = Event::factory()->create([
            Event::COMMENTS => 'Read me first.',
            Event::REQUIRE_MISSION_BRIEF_ACK => true,
        ]);
        EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($this->trooper())->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertSeeInOrder(['How signing up works', 'Read the mission brief first', 'Sign up for each shift']);
    }

    private function trooper(): Trooper
    {
        return Trooper::factory()->asActive()->withVerifiedEmail()->create();
    }

    private function showEvent(Trooper $trooper): \Illuminate\Testing\TestResponse
    {
        $event = Event::factory()->create();
        EventShift::factory()->forEvent($event)->create();

        return $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));
    }
}
