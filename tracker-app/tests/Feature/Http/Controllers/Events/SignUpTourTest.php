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
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The event page's "How sign-up works" tour (SignUpTour.svelte) and who it starts by itself
 * for (tracker.signup_tour). Its steps are covered by tests/js/svelte/events/vms.
 */
class SignUpTourTest extends TestCase
{
    use RefreshDatabase;

    public function test_by_default_it_starts_by_itself_for_a_trooper_who_has_not_attended_a_troop(): void
    {
        $this->showEvent($this->trooper())
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('tour.auto_start', true)
                ->where('tour.is_brief_pending', false));
    }

    public function test_by_default_it_waits_for_the_button_once_a_trooper_has_attended(): void
    {
        foreach ([EventTrooperStatus::ATTENDED, EventTrooperStatus::AWAITING_VERIFICATION] as $status)
        {
            $trooper = $this->trooper();
            EventTrooper::factory()->forTrooper($trooper)->create([EventTrooper::STATUS => $status]);

            $this->showEvent($trooper)->assertInertia(fn (Assert $page) => $page->where('tour.auto_start', false));
        }
    }

    public function test_a_sign_up_they_did_not_attend_still_counts_as_new(): void
    {
        $trooper = $this->trooper();
        EventTrooper::factory()->forTrooper($trooper)->create([EventTrooper::STATUS => EventTrooperStatus::NO_SHOW]);

        $this->showEvent($trooper)->assertInertia(fn (Assert $page) => $page->where('tour.auto_start', true));
    }

    public function test_everyone_starts_it_whatever_they_have_attended(): void
    {
        config(['tracker.signup_tour' => SignUpTour::EVERYONE]);
        $trooper = $this->trooper();
        EventTrooper::factory()->forTrooper($trooper)->create([EventTrooper::STATUS => EventTrooperStatus::ATTENDED]);

        $this->showEvent($trooper)->assertInertia(fn (Assert $page) => $page->where('tour.auto_start', true));
    }

    public function test_off_leaves_only_the_button(): void
    {
        config(['tracker.signup_tour' => SignUpTour::OFF]);

        $this->showEvent($this->trooper())->assertInertia(fn (Assert $page) => $page->where('tour.auto_start', false));
    }

    public function test_it_waits_for_the_button_when_no_shift_is_open(): void
    {
        config(['tracker.signup_tour' => SignUpTour::EVERYONE]);
        $event = Event::factory()->create();
        EventShift::factory()->forEvent($event)->asClosed()->create();

        $this->actingAs($this->trooper())->get(route('events.display', ['event' => $event->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('tour.auto_start', false));
    }

    public function test_it_is_remembered_per_trooper(): void
    {
        $trooper = $this->trooper();

        $this->showEvent($trooper)
            ->assertInertia(fn (Assert $page) => $page->where('tour.storage_key', 'tt.signupTourSeen.'.$trooper->id));
    }

    public function test_it_starts_with_the_mission_brief_when_it_must_be_acknowledged(): void
    {
        $event = Event::factory()->create([
            Event::COMMENTS => 'Read me first.',
            Event::REQUIRE_MISSION_BRIEF_ACK => true,
        ]);
        EventShift::factory()->forEvent($event)->create();

        $this->actingAs($this->trooper())->get(route('events.display', ['event' => $event->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('tour.is_brief_pending', true));
    }

    public function test_its_example_follows_the_event(): void
    {
        $this->showEvent($this->trooper())
            ->assertInertia(fn (Assert $page) => $page
                ->where('tour.going_label', EventTrooperStatus::GOING->label())
                ->has('tour.requires_costume')
                ->has('tour.has_plans')
                ->has('tour.plans'));
    }

    private function trooper(): Trooper
    {
        return Trooper::factory()->asActive()->withVerifiedEmail()->create();
    }

    private function showEvent(Trooper $trooper): TestResponse
    {
        $event = Event::factory()->create();
        EventShift::factory()->forEvent($event)->create();

        return $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));
    }
}
