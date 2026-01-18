<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Mail\Events\TrooperSignUp;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SignUpHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_event_trooper_when_shift_is_open_and_trooper_not_signed_up(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->open()->create([Event::SHIFTS_ALLOWED => null]);
        $event_shift = EventShift::factory()->for($event)->open()->create();

        $this->actingAs($trooper);

        $response = $this->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        $response->assertOk();
        $response->assertViewIs('pages.events.inc.shift-container');

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);

        Mail::assertQueued(TrooperSignUp::class);
    }

    public function test_invoke_does_not_create_event_trooper_when_trooper_already_signed_up(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->open()->create([Event::SHIFTS_ALLOWED => null]);
        $event_shift = EventShift::factory()->for($event)->open()->create();

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $this->actingAs($trooper);

        $response = $this->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        $response->assertOk();
        $response->assertViewIs('pages.events.inc.shift-container');

        $this->assertEquals(1, EventTrooper::where(EventTrooper::TROOPER_ID, $trooper->id)->count());

        Mail::assertNotQueued(TrooperSignUp::class);
    }

    public function test_invoke_does_not_create_event_trooper_when_shift_is_closed(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->open()->create([Event::SHIFTS_ALLOWED => null]);
        $event_shift = EventShift::factory()->for($event)->closed()->create();

        $this->actingAs($trooper);

        $response = $this->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        $response->assertOk();
        $response->assertViewIs('pages.events.inc.shift-container');

        $this->assertDatabaseMissing('tt_event_troopers', [
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
        ]);

        Mail::assertNotQueued(TrooperSignUp::class);
    }

    public function test_invoke_creates_event_trooper_when_shifts_allowed_is_null(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->open()->create([Event::SHIFTS_ALLOWED => null]);
        $event_shift_1 = EventShift::factory()->for($event)->open()->create();
        $event_shift_2 = EventShift::factory()->for($event)->open()->create();

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift_1->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $this->actingAs($trooper);

        $response = $this->post(route('events.signup-htmx', ['event_shift' => $event_shift_2->id]));

        $response->assertOk();
        $response->assertViewIs('pages.events.inc.shift-container');

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::EVENT_SHIFT_ID => $event_shift_2->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);

        Mail::assertQueued(TrooperSignUp::class);
    }

    public function test_invoke_creates_event_trooper_when_under_shifts_allowed_limit(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->open()->create([Event::SHIFTS_ALLOWED => 2]);
        $event_shift_1 = EventShift::factory()->for($event)->open()->create();
        $event_shift_2 = EventShift::factory()->for($event)->open()->create();

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift_1->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $this->actingAs($trooper);

        $response = $this->post(route('events.signup-htmx', ['event_shift' => $event_shift_2->id]));

        $response->assertOk();
        $response->assertViewIs('pages.events.inc.shift-container');

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::EVENT_SHIFT_ID => $event_shift_2->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);

        Mail::assertQueued(TrooperSignUp::class);
    }

    public function test_invoke_does_not_create_event_trooper_when_at_shifts_allowed_limit(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->open()->create([Event::SHIFTS_ALLOWED => 2]);
        $event_shift_1 = EventShift::factory()->for($event)->open()->create();
        $event_shift_2 = EventShift::factory()->for($event)->open()->create();
        $event_shift_3 = EventShift::factory()->for($event)->open()->create();

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift_1->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift_2->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $this->actingAs($trooper);

        $response = $this->post(route('events.signup-htmx', ['event_shift' => $event_shift_3->id]));

        $response->assertOk();
        $response->assertViewIs('pages.events.inc.shift-container');

        $this->assertDatabaseMissing('tt_event_troopers', [
            EventTrooper::EVENT_SHIFT_ID => $event_shift_3->id,
            EventTrooper::TROOPER_ID => $trooper->id,
        ]);

        Mail::assertNotQueued(TrooperSignUp::class);
    }

    public function test_invoke_places_trooper_in_going_status_when_shift_not_maxed(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->open()->create([
            Event::SHIFTS_ALLOWED => null,
            Event::TROOPERS_ALLOWED => 10,
            Event::HANDLERS_ALLOWED => 2,
        ]);
        $event_shift = EventShift::factory()->for($event)->open()->create();

        $this->actingAs($trooper);

        $response = $this->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        $response->assertOk();

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);
    }

    public function test_invoke_places_trooper_in_standby_when_troopers_maxed(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->open()->create([
            Event::SHIFTS_ALLOWED => null,
            Event::TROOPERS_ALLOWED => 1,
            Event::HANDLERS_ALLOWED => 2,
        ]);
        $event_shift = EventShift::factory()->for($event)->open()->create();

        $other_trooper = Trooper::factory()->asActive()->create();
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::TROOPER_ID => $other_trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
            EventTrooper::IS_HANDLER => false,
        ]);

        $this->actingAs($trooper);

        $response = $this->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        $response->assertOk();

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY->value,
        ]);
    }

    public function test_invoke_places_handler_in_standby_when_handlers_maxed(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->asActive()->asHandler()->create();
        $event = Event::factory()->open()->create([
            Event::SHIFTS_ALLOWED => null,
            Event::TROOPERS_ALLOWED => 10,
            Event::HANDLERS_ALLOWED => 1,
        ]);
        $event_shift = EventShift::factory()->for($event)->open()->create();

        $other_handler = Trooper::factory()->asActive()->asHandler()->create();
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::TROOPER_ID => $other_handler->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
            EventTrooper::IS_HANDLER => true,
        ]);

        $this->actingAs($trooper);

        $response = $this->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        $response->assertOk();

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY->value,
            EventTrooper::IS_HANDLER => true,
        ]);
    }

    public function test_invoke_can_sign_up_another_trooper_when_trooper_id_provided(): void
    {
        Mail::fake();

        $auth_trooper = Trooper::factory()->asActive()->create();
        $other_trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->open()->create([Event::SHIFTS_ALLOWED => null]);
        $event_shift = EventShift::factory()->for($event)->open()->create();

        $this->actingAs($auth_trooper);

        $response = $this->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]), [
            'trooper_id' => $other_trooper->id,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::TROOPER_ID => $other_trooper->id,
            EventTrooper::ADDED_BY_TROOPER_ID => $auth_trooper->id,
        ]);

        Mail::assertQueued(TrooperSignUp::class, function ($mail) use ($other_trooper)
        {
            return $mail->hasTo($other_trooper->email);
        });
    }

    public function test_invoke_sets_added_by_trooper_id_to_null_when_trooper_signs_up_self(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->open()->create([Event::SHIFTS_ALLOWED => null]);
        $event_shift = EventShift::factory()->for($event)->open()->create();

        $this->actingAs($trooper);

        $response = $this->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        $response->assertOk();

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::ADDED_BY_TROOPER_ID => null,
        ]);
    }

    public function test_invoke_returns_shift_container_view_with_event_data(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->open()->create([Event::SHIFTS_ALLOWED => null]);
        $event_shift = EventShift::factory()->for($event)->open()->create();

        $this->actingAs($trooper);

        $response = $this->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        $response->assertOk();
        $response->assertViewIs('pages.events.inc.shift-container');
        $response->assertViewHas('event');
        $response->assertViewHas('event_shift');
        $response->assertViewHas('can_moderate');
    }
}
