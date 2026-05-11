<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventGuestStatus;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestSignUpHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $trooper;

    private EventShift $event_shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($this->trooper)
            ->forOrganization($organization)
            ->asMember()
            ->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $this->event_shift = EventShift::factory()->forEvent($event)->create();
    }

    public function test_invoke_creates_guests_for_non_empty_escaped_newline_delimited_names(): void
    {
        EventTrooper::factory()->forEventShift($this->event_shift)->forTrooper($this->trooper)->asGoing()->create();

        $response = $this->actingAs($this->trooper)->post(
            route('events.guest-signup-htmx', ['event_shift' => $this->event_shift->id]),
            ['guest_names' => 'Leia Organa\\n\\n Han Solo \\n']
        );

        $response->assertOk();
        $response->assertHeader('HX-Trigger', 'event-shift-guest-added');

        $this->assertDatabaseHas('tt_event_guests', [
            EventGuest::EVENT_SHIFT_ID => $this->event_shift->id,
            EventGuest::ADDED_BY_TROOPER_ID => $this->trooper->id,
            EventGuest::NAME => 'Leia Organa',
            EventGuest::STATUS => EventGuestStatus::GOING->value,
        ]);

        $this->assertDatabaseHas('tt_event_guests', [
            EventGuest::EVENT_SHIFT_ID => $this->event_shift->id,
            EventGuest::ADDED_BY_TROOPER_ID => $this->trooper->id,
            EventGuest::NAME => 'Han Solo',
            EventGuest::STATUS => EventGuestStatus::GOING->value,
        ]);

        $this->assertSame(
            2,
            $this->event_shift->event_guests()->count()
        );
    }

    public function test_invoke_does_not_create_duplicate_guests_for_same_shift_and_trooper(): void
    {
        EventTrooper::factory()->forEventShift($this->event_shift)->forTrooper($this->trooper)->asGoing()->create();

        $payload = ['guest_names' => 'Chewbacca\\nChewbacca'];

        $this->actingAs($this->trooper)->post(
            route('events.guest-signup-htmx', ['event_shift' => $this->event_shift->id]),
            $payload
        )->assertOk();

        $this->actingAs($this->trooper)->post(
            route('events.guest-signup-htmx', ['event_shift' => $this->event_shift->id]),
            $payload
        )->assertOk();

        $this->assertSame(
            1,
            $this->event_shift->event_guests()->where(EventGuest::NAME, 'Chewbacca')->count()
        );
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->post(
            route('events.guest-signup-htmx', ['event_shift' => $this->event_shift->id]),
            ['guest_names' => 'Lando Calrissian']
        );

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_does_not_create_guests_when_trooper_not_going(): void
    {
        $this->actingAs($this->trooper)->post(
            route('events.guest-signup-htmx', ['event_shift' => $this->event_shift->id]),
            ['guest_names' => 'Lando Calrissian']
        )->assertOk();

        $this->assertDatabaseMissing('tt_event_guests', [
            EventGuest::EVENT_SHIFT_ID => $this->event_shift->id,
            EventGuest::NAME => 'Lando Calrissian',
        ]);
    }

    public function test_invoke_does_not_emit_trigger_when_trooper_not_going(): void
    {
        $response = $this->actingAs($this->trooper)->post(
            route('events.guest-signup-htmx', ['event_shift' => $this->event_shift->id]),
            ['guest_names' => 'Lando Calrissian']
        );

        $response->assertHeaderMissing('HX-Trigger');
    }
}
